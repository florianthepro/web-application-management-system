from pathlib import Path
import json,sys
r=Path(__file__).resolve().parents[1]
required=['install.sh','uninstall.sh','update-worker.sh','repository-sync-worker.php','public/index.php','src/Core.php','migrations/001.sql','applications/example-application/app.json']
errors=[f'fehlt: {x}' for x in required if not (r/x).is_file()]
for p in r.rglob('*.json'):
 try: json.loads(p.read_text())
 except Exception as e: errors.append(f'{p}: {e}')
example=json.loads((r/'applications/example-application/app.json').read_text())
if any(example['catalog'].values()):errors.append('Example Application ist sichtbar')
if (r/'applications/csv-reporting').exists():errors.append('CSV Reporting ist noch enthalten')
if errors:print('\n'.join(errors),file=sys.stderr);sys.exit(1)
print('Repository-Vertrag OK')
