import { copyFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const toolDirectory = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const sourceDirectory = resolve(toolDirectory, 'node_modules', 'echarts', 'theme');
const targetDirectory = resolve(toolDirectory, '..', '..', 'libs', 'echarts', '6.1.0', 'themes');
const themeNames = ['dark', 'vintage', 'macarons', 'infographic', 'shine', 'roma'];

mkdirSync(targetDirectory, { recursive: true });
for (const themeName of themeNames) {
    copyFileSync(
        resolve(sourceDirectory, `${themeName}.js`),
        resolve(targetDirectory, `${themeName}.js`)
    );
}
