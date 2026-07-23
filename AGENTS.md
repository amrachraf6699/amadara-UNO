# Repository instructions

## Required workflow

- After every repository edit, run the relevant checks for the changed area.
- After the checks pass, automatically create a Git commit containing all intended working-tree changes.
- Push the commit to the current branch's configured remote immediately after committing.
- Before committing, inspect `git status` and the diff so unrelated user changes are preserved and included only when they are part of the requested work.
- Do not use destructive Git commands such as `git reset --hard` or `git checkout --` unless the user explicitly requests them.

## Verification

- For Blade changes, run `php artisan view:cache`.
- For application behavior changes, run the most relevant PHPUnit feature tests.
- Run `git diff --check` before committing.

## Handoff

- Report the commit hash, push result, and verification commands in the final response.
