const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const rootDir = path.resolve(__dirname, '..');
const versionFile = path.join(rootDir, 'VERSION');

// 1. Determine Target Version
let targetVersion = process.argv[2];

if (!targetVersion) {
  if (fs.existsSync(versionFile)) {
    targetVersion = fs.readFileSync(versionFile, 'utf8').trim();
  } else {
    targetVersion = '1.0.6';
  }
}

// Strip leading 'v' if present (e.g. v1.0.6 -> 1.0.6)
targetVersion = targetVersion.replace(/^v/i, '').trim();

console.log(`\n🚀 Synchronizing Genlytics Version to v${targetVersion}...\n`);

// 2. Ensure VERSION file is up to date
fs.writeFileSync(versionFile, targetVersion + '\n');
console.log(`  ✓ Updated VERSION -> ${targetVersion}`);

// 3. Update src/Genlytics.php
const genlyticsPhp = path.join(rootDir, 'src', 'Genlytics.php');
if (fs.existsSync(genlyticsPhp)) {
  let content = fs.readFileSync(genlyticsPhp, 'utf8');
  content = content.replace(
    /public const VERSION = '[^']+';/,
    `public const VERSION = '${targetVersion}';`
  );
  fs.writeFileSync(genlyticsPhp, content);
  console.log(`  ✓ Updated src/Genlytics.php (VERSION constant -> '${targetVersion}')`);
}

// 4. Update config/analytics.php
const configAnalytics = path.join(rootDir, 'config', 'analytics.php');
if (fs.existsSync(configAnalytics)) {
  let content = fs.readFileSync(configAnalytics, 'utf8');
  content = content.replace(
    /'version' => env\('GENLYTICS_VERSION', '[^']+'\)/,
    `'version' => env('GENLYTICS_VERSION', '${targetVersion}')`
  );
  fs.writeFileSync(configAnalytics, content);
  console.log(`  ✓ Updated config/analytics.php ('version' -> '${targetVersion}')`);
}

// 5. Update website/package.json
const websitePackageJson = path.join(rootDir, 'website', 'package.json');
if (fs.existsSync(websitePackageJson)) {
  try {
    const pkg = JSON.parse(fs.readFileSync(websitePackageJson, 'utf8'));
    pkg.version = targetVersion;
    fs.writeFileSync(websitePackageJson, JSON.stringify(pkg, null, 2) + '\n');
    console.log(`  ✓ Updated website/package.json (version -> ${targetVersion})`);
  } catch (e) {
    console.warn(`  ! Could not parse website/package.json: ${e.message}`);
  }
}

// 6. Update Test Files containing expected version assertions
const testFiles = [
  path.join(rootDir, 'tests', 'Unit', 'GenlyticsTest.php'),
  path.join(rootDir, 'tests', 'Unit', 'Http', 'ResourcesTest.php'),
  path.join(rootDir, 'tests', 'Unit', 'Query', 'AnalyticsQueryBuilderTest.php')
];

testFiles.forEach(file => {
  if (fs.existsSync(file)) {
    let content = fs.readFileSync(file, 'utf8');
    content = content.replace(/assertEquals\('[0-9]+\.[0-9]+\.[0-9]+'/g, `assertEquals('${targetVersion}'`);
    fs.writeFileSync(file, content);
    console.log(`  ✓ Updated test assertions in ${path.relative(rootDir, file)}`);
  }
});

// 7. Update Markdown documentation references
const docFiles = [
  path.join(rootDir, 'website', 'src', 'content', 'dtos-and-resources.md'),
  path.join(rootDir, 'website', 'src', 'content', 'getting-started.md'),
  path.join(rootDir, 'website', 'src', 'content', 'quota-and-limits.md')
];

docFiles.forEach(file => {
  if (fs.existsSync(file)) {
    let content = fs.readFileSync(file, 'utf8');
    content = content.replace(/1\.0\.[0-9]+/g, targetVersion);
    fs.writeFileSync(file, content);
    console.log(`  ✓ Updated doc version in ${path.relative(rootDir, file)}`);
  }
});

// 8. Rebuild website docs/ assets
try {
  const buildScript = path.join(rootDir, 'website', 'build.js');
  if (fs.existsSync(buildScript)) {
    console.log('\n📦 Rebuilding Documentation Website assets...');
    execSync(`node "${buildScript}"`, { stdio: 'inherit' });
  }
} catch (e) {
  console.error(`  ! Error during website build: ${e.message}`);
}

console.log(`\n🎉 Successfully synchronized all version references to v${targetVersion}!\n`);

