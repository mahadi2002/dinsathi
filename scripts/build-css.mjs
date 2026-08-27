// Minimal CSS build step: public/assets/css/app.css -> public/assets/dist/app.css.
//
// Split out from vite.config.js because Rollup's 'iife' output format
// (used for app.js so it loads as a plain classic <script defer>, not an
// ES module) does not support multiple entry points in one build. Rather
// than switch app.js to an ES module just to let Rollup bundle two files
// at once, this reaches for esbuild's own CSS minifier directly — esbuild
// is already installed as Vite's bundler dependency, so this adds no new
// package.
//
// Run by `npm run build` right after `vite build` (which empties
// public/assets/dist/ and writes app.js there); this script only adds
// app.css into that same folder afterward and never touches app.js.
import { build, context } from 'esbuild';
import { mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const root = dirname(dirname(fileURLToPath(import.meta.url)));
const entry = resolve(root, 'public/assets/css/app.css');
const outfile = resolve(root, 'public/assets/dist/app.css');
const watch = process.argv.includes('--watch');

mkdirSync(dirname(outfile), { recursive: true });

const options = {
  entryPoints: [entry],
  outfile,
  minify: true,
  logLevel: 'info',
};

if (watch) {
  const ctx = await context(options);
  await ctx.watch();
  console.log('[build-css] watching public/assets/css/app.css for changes...');
} else {
  await build(options);
}
