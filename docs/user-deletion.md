# What deleting a user removes

The Users page performs a permanent database deletion, not a soft deletion.
The delete dialog has a five-second countdown and explains the affected data.
These effects follow the foreign keys declared in the project's migrations:

| Removed record | Related information removed |
| --- | --- |
| User account | Name, email, password hash, role, account status, verification and consent timestamps, reminder timestamp, remember token |
| Graduate tracer survey | Draft or completed answers, program/year association, submission timestamp, advance-study reasons |
| General information and address | Contact details, birthday, sex, civil status, origin and residence information, permanent/current addresses |
| Education and training | Educational background, professional exams, course-choice reasons, training records |
| Employment | Employment status, occupation, business line, job history, earnings range, curriculum relevance/suggestions, skills stored in the employment row |
| Employment child records | Unemployment reasons, reasons for staying/accepting/changing jobs, competencies, recorded location |
| Referred graduates | Names, addresses and numbers the deleted graduate entered in the optional alumni referral section |
| Graduate program registrations | Links between this user and their academic program/school year |
| GCash reward record | Submitted reward number and Done/Pending status |

Their answers disappear from subsequent dashboard and analytics calculations,
the template export list, integrations list and graduate map. Other accounts,
academic program definitions, departments, school years, and the blank DOCX
template remain.

The controller records a `user_deleted` audit entry before deleting the account.
Audit history remains, including the deleted user's email in this entry's
metadata. If the deleted user was the actor of earlier audit entries, their
actor foreign key becomes null; those audit entries are retained.

Deleting the account does not erase downloaded Word/CSV exports, backups,
already-sent emails, server logs, browser-local survey drafts, session rows or
password-reset-token rows. Sessions no longer authenticate because the account
no longer exists. The current delete action has no explicit cleanup for those
other stores.

There is also a `SelfEmployedSkill` model referring to a separate skills table,
but the repository has no migration declaring that table or its foreign key.
Whether separately stored skill rows cascade must be checked against the actual
installed database schema; this cannot be guaranteed from the repository.

These conclusions assume the production database has the migration-defined
foreign keys installed and enabled. No production data was deleted during this
update. Restoring deleted survey data requires a backup; registering the same
email again creates a new account and does not recover old answers.

To block access while preserving survey history, use Edit and set the account
status to Inactive. Reporting currently selects by graduate role and submission
status, so an inactive graduate's completed survey still contributes to reports.
