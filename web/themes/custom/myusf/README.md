# MyUSF Custom Theme

This is the custom Drupal theme for the University of San Francisco's "MyUSF" site.

## 📁 Directory Structure

```
web/themes/custom/myusf/
├── css/               # Compiled CSS output
├── images/            # Theme image assets
├── npm-shrinkwrap.json (optional legacy)
├── package.json       # Project dependencies
├── package-lock.json  # Dependency lock file
├── gulpfile.js        # Gulp task runner config
├── sass/              # SCSS source files
├── scripts/           # JS source files
└── node_modules/      # Node dependencies (generated)
```

## 🚀 Getting Started

### 1. Start Lando

lando start

### 2. Install Dependencies

Navigate to the theme directory:

cd web/themes/custom/myusf
lando npm install

This will install the Node dependencies listed in `package.json`. Make sure `package-lock.json` is committed to maintain consistency.

### 3. Build Assets

lando gulp

Gulp will:

* Compile SCSS into CSS
* Minify and bundle JS
* Optimize image assets

## 💡 Notes for Developers

* **Node version**: Node 18 is used via Lando.
* **Gulp CLI** is available via `lando gulp`. No global install needed.
* **Lock files**: Always commit `package.json` and `package-lock.json` when changing dependencies.
* **CircleCI** builds and compiles theme assets automatically on commit.

## 🧪 CI/CD

CircleCI is configured to:

* Run PHP linting and unit tests
* Build theme assets using Gulp
* Deploy to Pantheon

To trigger the pipeline, push changes to GitHub.

## 🙋‍♀️ Support

Contact the web development team or refer to the `lando.yml` file for advanced configurations.
[redeploy]