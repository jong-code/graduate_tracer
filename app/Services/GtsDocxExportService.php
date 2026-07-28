<?php

namespace App\Services;

use App\Models\GraduateTracerSurvey;
use Illuminate\Support\Facades\Process;

class GtsDocxExportService
{
    public function __construct(private GtsDocxDataMapper $mapper)
    {
    }

    /**
     * Render the given survey into a filled-in .docx using the currently
     * configured template. Returns the absolute path of the generated file
     * (in a temp location - the caller is responsible for streaming and
     * cleaning it up).
     *
     * @throws \RuntimeException if the template is missing or the Node
     *                            render script fails (e.g. a template tag
     *                            that doesn't match anything this mapper
     *                            produces).
     */
    public function export(GraduateTracerSurvey $survey): string
    {
        $survey->loadMissing([
            'generalInformation', 'educationalBackgrounds', 'professionalExams',
            'courseReasons', 'trainings', 'employmentData.notEmployedReasons',
            'employmentData.jobReasons', 'employmentData.competencies',
            'otherGraduates',
        ]);

        $templatePath = public_path('templates/gts_template.docx');
        if (! is_file($templatePath)) {
            throw new \RuntimeException('No survey template has been uploaded yet. Upload one from Admin > Survey Templates.');
        }

        $data = $this->mapper->map($survey);

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }
        $token = uniqid('gts_export_', true);
        $dataPath = $tmpDir . "/{$token}.json";
        $outputPath = $tmpDir . "/{$token}.docx";

        file_put_contents($dataPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $scriptPath = base_path('docx-export/render.js');

        // On Windows, PHP's process spawning doesn't always forward the
        // full parent environment to the child - SystemRoot in particular
        // is a common casualty. Node needs SystemRoot to initialize its
        // crypto RNG at startup (it calls into Windows' CNG/BCrypt APIs),
        // and without it Node crashes immediately with an
        // "Assertion failed: ncrypto::CSPRNG" error before any of our JS
        // even runs. Forcing these through explicitly fixes that even when
        // the ambient environment inheritance doesn't.
        $env = [];
        foreach (['SystemRoot', 'windir', 'PATH', 'TEMP', 'TMP', 'ComSpec'] as $key) {
            $value = getenv($key);
            if ($value !== false) {
                $env[$key] = $value;
            }
        }

        $result = Process::env($env)->timeout(30)->run(['node', $scriptPath, $templatePath, $dataPath, $outputPath]);

        @unlink($dataPath);

        if ($result->failed()) {
            throw new \RuntimeException('Could not generate the DOCX: ' . trim($result->errorOutput() ?: $result->output()));
        }

        return $outputPath;
    }
}
