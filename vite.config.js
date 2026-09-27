import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import path from "path";
import { fileURLToPath } from "url";

const projectRoot = path.dirname(fileURLToPath(import.meta.url));
const ckeditorRoot = path.resolve(projectRoot, "public/ckeditor/ckeditor5");

export default defineConfig({
  resolve: {
    alias: [
      {
        find: /^ckeditor5$/,
        replacement: path.join(ckeditorRoot, "ckeditor5.js"),
      },
      {
        find: /^ckeditor5\/(.*)/,
        replacement: `${ckeditorRoot}/$1`,
      },
    ],
  },
  plugins: [
    laravel({
      input: ["resources/css/app.css", "resources/js/app.js"],
      refresh: true,
    }),
  ],
  build: {
    chunkSizeWarningLimit: 1500,
  },
});
