import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const TARGETS = {
  admin: {
    input: './resources/css/admin/dark/main.css',
    output: './public/build/assets/admin-main-dark.css',
  },
  business: {
    input: './resources/css/business/dark/main.css',
    output: './public/build/assets/business-main-dark.css',
  },
  desktop: {
    input: './resources/css/spa/apps/desktop/dark/main.css',
    output: './public/build/assets/desktop-main-dark.css',
  },
  mobile: {
    input: './resources/css/spa/apps/mobile/dark/main.css',
    output: './public/build/assets/mobile-main-dark.css',
  },
  auth: {
    input: './resources/css/spa/apps/desktop/dark/auth.css',
    output: './public/build/assets/desktop-auth-dark.css',
  },
};

const targetName = process.argv[2];
const target = TARGETS[targetName];

if (!target) {
  console.error(`Unknown dark build target: ${targetName}`);
  process.exit(1);
}

const outDir = path.dirname(target.output);
fs.mkdirSync(outDir, { recursive: true });

const npx = process.platform === 'win32' ? 'npx.cmd' : 'npx';
const args = ['@tailwindcss/cli', '-i', target.input, '-o', target.output];
const result = spawnSync(npx, args, { stdio: 'inherit' });

if (result.status === 0) {
  process.exit(0);
}

if (fs.existsSync(target.output) && fs.statSync(target.output).size > 0) {
  console.warn(`Using existing dark CSS for ${targetName} after Tailwind CLI failure.`);
  process.exit(0);
}

fs.copyFileSync(target.input, target.output);
console.warn(`Fallback copied source CSS for ${targetName}; Tailwind CLI failed.`);
process.exit(0);
