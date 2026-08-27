import { defineConfig } from 'vite';
import { resolve } from 'node:path';

// Minimal build step for public/assets/js/app.js: one hand-written,
// import-free file in, one minified file out at the same basename in
// public/assets/dist/ — no code-splitting, no hashed filenames, no
// framework. format: 'iife' (not 'es') keeps it loadable as a plain
// classic <script defer>.
//
// app.css is deliberately NOT built here — see scripts/build-css.mjs
// (run right after this by `npm run build`) for why and how it's handled
// instead.
//
// public/assets/js/app.js and public/assets/css/app.css (the source files)
// stay in place and are still what app/Core/Helpers.php's asset() falls
// back to when dist/ hasn't been built (e.g. a fresh checkout before
// `npm install && npm run build`), so the app keeps working with zero
// Node dependency at runtime.
export default defineConfig({
  // Vite's default publicDir ("public/" at the project root) gets copied
  // wholesale into outDir on every build. outDir here (public/assets/dist)
  // lives *inside* that same public/ folder, so leaving the default on
  // means each build copies the previous build's dist/ output back into
  // itself — a self-nesting dist/assets/dist/assets/... tree that grows
  // one level deeper every run. public/ is the PHP app's webroot, not a
  // static-assets folder Vite needs to manage, so this is switched off.
  publicDir: false,
  build: {
    outDir: 'public/assets/dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'public/assets/js/app.js'),
      },
      output: {
        format: 'iife',
        entryFileNames: 'app.js',
      },
    },
  },
});
