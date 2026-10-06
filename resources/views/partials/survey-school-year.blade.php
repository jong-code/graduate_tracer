<select name="{{ $fieldName }}" class="form-select form-select-sm" aria-label="Year Graduated / School Year" required>
    <option value="">Select school year graduated</option>
    @foreach ($schoolYears as $year)
        <option value="{{ $year->label }}" @selected((string) $selectedYear === (string) $year->label)>{{ $year->label }}</option>
    @endforeach
</select>
