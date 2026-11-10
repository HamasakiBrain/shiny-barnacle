import { mkdir, copyFile } from 'node:fs/promises';
await mkdir('public/vendor/swagger-ui', {recursive: true});
for (const file of ['swagger-ui-bundle.js', 'swagger-ui.css', 'LICENSE']) {
    await copyFile('node_modules/swagger-ui-dist/' + file, 'public/vendor/swagger-ui/' + file);
}
