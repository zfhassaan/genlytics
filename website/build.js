const fs = require('fs');
const path = require('path');

const websiteDir = __dirname;
const contentDir = path.join(websiteDir, 'src', 'content');
const docsDir = path.join(websiteDir, '..', 'docs');
const pkgDir = path.join(websiteDir, '..');

// Load Version strictly from the VERSION file
const versionFilePath = path.join(pkgDir, 'VERSION');
let packageVersion = '1.0.5';
if (fs.existsSync(versionFilePath)) {
  packageVersion = fs.readFileSync(versionFilePath, 'utf8').trim();
}

// Ensure output docs directory exists
if (!fs.existsSync(docsDir)) {
  fs.mkdirSync(docsDir, { recursive: true });
}

console.log(`Building Genlytics Vue.js Documentation Website (v${packageVersion})...`);
console.log(`  ✓ Loaded active version from VERSION file: ${packageVersion}`);

// Read all markdown files in src/content
const docsContent = {};
const markdownFiles = [
  'getting-started',
  'fluent-queries',
  'ecommerce-and-funnels',
  'quota-and-limits',
  'dtos-and-resources',
  'architecture',
  'configuration',
  'api-reference',
  'batch-reporting',
  'test-coverage',
  'faq'
];

for (const name of markdownFiles) {
  const filePath = path.join(contentDir, `${name}.md`);
  if (fs.existsSync(filePath)) {
    let raw = fs.readFileSync(filePath, 'utf8');
    raw = raw.split('{{VERSION}}').join(packageVersion);
    docsContent[name] = raw;
    console.log(`  ✓ Loaded content: ${name}.md (${docsContent[name].length} bytes)`);
  } else {
    console.warn(`  ! Warning: Missing ${filePath}`);
    docsContent[name] = `# ${name}\n\nContent coming soon.`;
  }
}

// Generate app.js with embedded documentation and Vue 3 App logic
const appJsContent = `// Genlytics Documentation Vue 3 Application
const { createApp, ref, computed, watch, onMounted, onUnmounted, nextTick } = Vue;

const docsContent = ${JSON.stringify(docsContent, null, 2)};

const app = createApp({
  setup() {
    const packageVersion = ref('${packageVersion}');
    const currentPage = ref('home');
    const lastDocPage = ref('getting-started');
    const isDarkMode = ref(false);
    const docsSearch = ref('');
    const isSearchModalOpen = ref(false);
    const selectedSearchIndex = ref(0);
    const modalSearchInputRef = ref(null);
    const copiedText = ref('');
    const studioTab = ref('fluent');
    const selectedHelper = ref('topPages');
    const batchPreset = ref('full_dashboard');

    const docNavSections = [
      {
        group: 'Getting Started',
        items: [
          { id: 'getting-started', title: 'Installation & GCP Setup', icon: 'play-circle' },
          { id: 'configuration', title: 'Configuration & Caching', icon: 'settings' }
        ]
      },
      {
        group: 'Developer Experience',
        items: [
          { id: 'fluent-queries', title: 'Fluent Query Builder & Helpers', icon: 'terminal' },
          { id: 'ecommerce-and-funnels', title: 'E-Commerce & Conversion Funnels', icon: 'git-merge' },
          { id: 'dtos-and-resources', title: 'Response DTOs & API Resources', icon: 'box' },
          { id: 'batch-reporting', title: 'Batch Reporting (Multi-Widget)', icon: 'cpu' }
        ]
      },
      {
        group: 'Architecture',
        items: [
          { id: 'architecture', title: 'SOLID & Repository Pattern', icon: 'layers' },
          { id: 'quota-and-limits', title: 'GA4 Quota & Limits (200k/day)', icon: 'gauge' },
          { id: 'test-coverage', title: 'Test Coverage & Verification (90.4%)', icon: 'shield-check' }
        ]
      },
      {
        group: 'Reference',
        items: [
          { id: 'api-reference', title: 'Complete API Reference', icon: 'code' },
          { id: 'faq', title: 'Troubleshooting & FAQ', icon: 'help-circle' }
        ]
      }
    ];

    const docsCatalog = [];
    docNavSections.forEach(sec => {
      sec.items.forEach(item => {
        docsCatalog.push({ ...item, group: sec.group });
      });
    });

    const escapeRegex = (str) => {
      return str.replace(/[.*+?^$\\{}()|[\\]\\\\]/g, '\\\\$&');
    };

    const highlightText = (text, query) => {
      if (!text || !query) return text || '';
      try {
        const escaped = escapeRegex(query);
        return text.replace(new RegExp('(' + escaped + ')', 'gi'), '<mark class="search-highlight">$1</mark>');
      } catch (e) {
        return text;
      }
    };

    const searchResults = computed(() => {
      const q = (docsSearch.value || '').trim();
      if (!q) return [];
      const qLower = q.toLowerCase();
      const results = [];

      docsCatalog.forEach(doc => {
        const rawContent = docsContent[doc.id] || '';
        const titleMatch = doc.title.toLowerCase().includes(qLower);
        const groupMatch = doc.group.toLowerCase().includes(qLower);

        let matchCount = titleMatch ? 2 : 0;
        try {
          const contentMatches = rawContent.match(new RegExp(escapeRegex(q), 'gi'));
          if (contentMatches) matchCount += contentMatches.length;
        } catch (e) {}

        if (matchCount === 0 && !groupMatch) {
          return;
        }

        let bestHeading = '';
        let bestSnippet = '';
        const lines = rawContent.split('\\n');
        let currentHeading = '';

        for (let i = 0; i < lines.length; i++) {
          const rawLine = lines[i];
          const trimmed = rawLine.trim();

          if (trimmed.startsWith('#')) {
            currentHeading = trimmed.replace(/^#+\\s*/, '').trim();
            if (currentHeading.toLowerCase().includes(qLower) && !bestHeading) {
              bestHeading = currentHeading;
            }
          } else if (trimmed.toLowerCase().includes(qLower) && !bestSnippet) {
            const clean = trimmed
              .split(String.fromCharCode(96)).join('')
              .replace(/\\*\\*([^*]+)\\*\\*/g, '$1')
              .replace(/\\*([^*]+)\\*/g, '$1')
              .replace(/\\[([^\\]]+)\\]\\([^)]+\\)/g, '$1')
              .replace(/^[>\\-\\*[0-9]\\.\\s|:]+/, '')
              .trim();

            if (clean.length > 5) {
              const idx = clean.toLowerCase().indexOf(qLower);
              const start = Math.max(0, idx - 45);
              const end = Math.min(clean.length, idx + q.length + 65);
              let snip = clean.slice(start, end).replace(/\\s+/g, ' ').trim();
              if (start > 0) snip = '...' + snip;
              if (end < clean.length) snip = snip + '...';
              bestSnippet = snip;
              if (!bestHeading && currentHeading) {
                bestHeading = currentHeading;
              }
            }
          }
        }

        let score = 0;
        if (titleMatch) score += 100;
        if (bestHeading && bestHeading.toLowerCase().includes(qLower)) score += 50;
        score += Math.min(matchCount * 2, 50);

        results.push({
          id: doc.id,
          title: doc.title,
          group: doc.group,
          icon: doc.icon,
          heading: bestHeading,
          snippet: bestSnippet,
          matchCount: Math.max(1, matchCount),
          score: score,
          highlightedTitle: highlightText(doc.title, q),
          highlightedHeading: highlightText(bestHeading, q),
          highlightedSnippet: highlightText(bestSnippet, q)
        });
      });

      return results.sort((a, b) => b.score - a.score);
    });

    const popularSearchTags = [
      'quota',
      'cache',
      'fluent',
      'resources',
      'funnel',
      'batch',
      'test-coverage',
      'events'
    ];

    const quickJumpDocs = [
      { id: 'getting-started', title: 'Installation & Setup', desc: 'Composer require, config publishing, service account credentials', group: 'Getting Started', icon: 'play-circle' },
      { id: 'fluent-queries', title: 'Fluent Query Builder', desc: 'Query GA4 dimensions & metrics with Eloquent syntax', group: 'Developer Experience', icon: 'terminal' },
      { id: 'quota-and-limits', title: 'GA4 Quota & Limits', desc: '200k daily tokens, PST midnight auto-reset, burst pacing', group: 'Architecture', icon: 'gauge' },
      { id: 'ecommerce-and-funnels', title: 'E-Commerce Funnels', desc: 'Multi-step dropoff analysis, top products and categories', group: 'Developer Experience', icon: 'git-merge' },
      { id: 'dtos-and-resources', title: 'DTOs & API Resources', desc: 'Strictly-typed responses, automatic package versioning', group: 'Developer Experience', icon: 'box' },
      { id: 'test-coverage', title: 'Test Coverage & Verification', desc: '160 automated tests, 484 assertions, 90.43% code coverage', group: 'Architecture', icon: 'shield-check' }
    ];

    const openSearchModal = (initialQuery = '') => {
      isSearchModalOpen.value = true;
      if (typeof initialQuery === 'string' && initialQuery.trim()) {
        docsSearch.value = initialQuery;
      }
      selectedSearchIndex.value = 0;
      nextTick(() => {
        if (modalSearchInputRef.value) {
          modalSearchInputRef.value.focus();
          modalSearchInputRef.value.select();
        }
        refreshLucideIcons();
      });
    };

    const closeSearchModal = () => {
      isSearchModalOpen.value = false;
      refreshLucideIcons();
    };

    const setSearchQuery = (tag) => {
      docsSearch.value = tag;
      selectedSearchIndex.value = 0;
      nextTick(() => {
        if (modalSearchInputRef.value) {
          modalSearchInputRef.value.focus();
        }
        refreshLucideIcons();
      });
    };

    const navigateSearchResults = (delta) => {
      if (!searchResults.value || searchResults.value.length === 0) return;
      const count = searchResults.value.length;
      selectedSearchIndex.value = (selectedSearchIndex.value + delta + count) % count;
      nextTick(() => {
        const activeItem = document.querySelector('.search-modal-result-active');
        if (activeItem && typeof activeItem.scrollIntoView === 'function') {
          activeItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
      });
    };

    const selectActiveSearchResult = () => {
      if (!searchResults.value || searchResults.value.length === 0) return;
      const target = searchResults.value[selectedSearchIndex.value] || searchResults.value[0];
      if (target) {
        selectSearchResult(target);
      }
    };

    const selectSearchResult = (result) => {
      navigateTo(result.id);
      closeSearchModal();
      nextTick(() => {
        refreshLucideIcons();
      });
    };

    const navigateToDoc = (docId) => {
      navigateTo(docId);
      closeSearchModal();
      nextTick(() => {
        refreshLucideIcons();
      });
    };

    const onSearchEnter = () => {
      if (searchResults.value && searchResults.value.length > 0) {
        selectSearchResult(searchResults.value[0]);
      }
    };

    watch(docsSearch, () => {
      selectedSearchIndex.value = 0;
      nextTick(() => {
        refreshLucideIcons();
      });
    });

    watch(isSearchModalOpen, (isOpen) => {
      try {
        if (isOpen) {
          document.body.style.overflow = 'hidden';
        } else if (!isMobileMenuOpen.value && !isMobileDocsDrawerOpen.value) {
          document.body.style.overflow = '';
        }
      } catch (e) {}
      nextTick(() => {
        refreshLucideIcons();
      });
    });

    // Mobile & Tablet Responsive Navigation State
    const isMobileMenuOpen = ref(false);
    const isMobileDocsDrawerOpen = ref(false);

    const toggleMobileMenu = () => {
      isMobileMenuOpen.value = !isMobileMenuOpen.value;
      if (isMobileMenuOpen.value) {
        isMobileDocsDrawerOpen.value = false;
        try { document.body.style.overflow = 'hidden'; } catch (e) {}
      } else {
        try { document.body.style.overflow = ''; } catch (e) {}
      }
      refreshLucideIcons();
    };

    const closeMobileMenu = () => {
      isMobileMenuOpen.value = false;
      if (!isSearchModalOpen.value && !isMobileDocsDrawerOpen.value) {
        try { document.body.style.overflow = ''; } catch (e) {}
      }
      refreshLucideIcons();
    };

    const toggleMobileDocsDrawer = () => {
      isMobileDocsDrawerOpen.value = !isMobileDocsDrawerOpen.value;
      if (isMobileDocsDrawerOpen.value) {
        isMobileMenuOpen.value = false;
        try { document.body.style.overflow = 'hidden'; } catch (e) {}
      } else {
        try { document.body.style.overflow = ''; } catch (e) {}
      }
      refreshLucideIcons();
    };

    const closeMobileDocsDrawer = () => {
      isMobileDocsDrawerOpen.value = false;
      if (!isSearchModalOpen.value && !isMobileMenuOpen.value) {
        try { document.body.style.overflow = ''; } catch (e) {}
      }
      refreshLucideIcons();
    };

    const getCurrentDocTitle = computed(() => {
      if (!isDocPage(currentPage.value)) return '';
      for (let i = 0; i < docNavSections.length; i++) {
        const found = docNavSections[i].items.find(item => item.id === currentPage.value);
        if (found) return found.title;
      }
      return 'Documentation';
    });

    const getCurrentDocGroup = computed(() => {
      if (!isDocPage(currentPage.value)) return '';
      for (let i = 0; i < docNavSections.length; i++) {
        const found = docNavSections[i].items.find(item => item.id === currentPage.value);
        if (found) return docNavSections[i].group;
      }
      return 'Guide';
    });

    // Live Real-Time Simulator Data
    const liveStats = ref({
      activeUsers: 148,
      sessions: 14280,
      views: 52140
    });

    // GA4 Quota & Limits Engine State
    const quotaPropertyType = ref('standard');
    const quotaMode = ref('maximum_utilization');
    const quotaDailyConsumed = ref(18420);
    const quotaHourlyConsumed = ref(3250);
    const quotaConcurrentActive = ref(2);
    const quotaProjectRequests = ref(842);
    const quotaPstCountdown = ref('12h 45m 10s');
    const quotaPacingStatus = ref('optimal');

    const quotaDailyLimit = computed(() => quotaPropertyType.value === '360' ? 2000000 : 200000);
    const quotaDailyRemaining = computed(() => Math.max(0, quotaDailyLimit.value - quotaDailyConsumed.value));
    const quotaDailyPercent = computed(() => ((quotaDailyConsumed.value / quotaDailyLimit.value) * 100).toFixed(1));
    const quotaHourlyLimit = 40000;
    const quotaHourlyRemaining = computed(() => Math.max(0, quotaHourlyLimit - quotaHourlyConsumed.value));
    const quotaHourlyPercent = computed(() => ((quotaHourlyConsumed.value / quotaHourlyLimit) * 100).toFixed(1));
    const quotaWarningThreshold = computed(() => {
      if (quotaMode.value === 'consume_all') return 100;
      return quotaMode.value === 'maximum_utilization' ? 99 : 85;
    });
    const quotaPacingBadge = computed(() => {
      if (quotaMode.value === 'consume_all') return '🔥 CONSUME ALL (100% DRAIN)';
      return quotaMode.value === 'maximum_utilization' ? 'MAX UTILIZATION (100% BURST)' : 'BALANCED (85% SAFE)';
    });
    const quotaUserLevelLimit = computed(() => {
      if (quotaMode.value === 'consume_all' || quotaMode.value === 'maximum_utilization') {
        return '1,000 req / 100s (Expanded)';
      }
      return '100 req / 100s (Default)';
    });

    const realTimeTelemetry = ref([
      { id: 1, event: 'QuotaUsageUpdated', tokens: 12, time: 'Just now' },
      { id: 2, event: 'QuotaUsageUpdated', tokens: 8, time: '3s ago' },
      { id: 3, event: 'QuotaUsageUpdated', tokens: 25, time: '7s ago' }
    ]);
    const drainInProgress = ref(false);

    function updatePstCountdown() {
      try {
        const now = new Date();
        const pstStr = now.toLocaleString('en-US', { timeZone: 'America/Los_Angeles' });
        const pstDate = new Date(pstStr);
        const pstMidnight = new Date(pstDate);
        pstMidnight.setHours(24, 0, 0, 0);
        const diffMs = Math.max(0, pstMidnight - pstDate);
        const hours = Math.floor(diffMs / 3600000);
        const mins = Math.floor((diffMs % 3600000) / 60000);
        const secs = Math.floor((diffMs % 60000) / 1000);
        quotaPstCountdown.value = \`\${hours}h \${String(mins).padStart(2, '0')}m \${String(secs).padStart(2, '0')}s\`;
      } catch (e) {
        quotaPstCountdown.value = 'Midnight PST';
      }
    }

    function simulateQuotaExecution(isHeavy = false) {
      quotaConcurrentActive.value = Math.min(10, quotaConcurrentActive.value + (isHeavy ? 3 : 1));
      const tokens = isHeavy ? (Math.floor(Math.random() * 80) + 120) : (Math.floor(Math.random() * 15) + 5);
      quotaDailyConsumed.value += tokens;
      quotaHourlyConsumed.value += tokens;
      quotaProjectRequests.value += (isHeavy ? 3 : 1);
      
      realTimeTelemetry.value.unshift({
        id: Date.now(),
        event: 'QuotaUsageUpdated',
        tokens: tokens,
        time: 'Just now'
      });
      if (realTimeTelemetry.value.length > 4) realTimeTelemetry.value.pop();

      setTimeout(() => {
        quotaConcurrentActive.value = Math.max(1, quotaConcurrentActive.value - (isHeavy ? 3 : 1));
      }, 700);
    }

    function simulateDrainDailyQuota() {
      drainInProgress.value = true;
      quotaMode.value = 'consume_all';
      let count = 0;
      const interval = setInterval(() => {
        count++;
        const tokens = Math.floor(Math.random() * 850) + 400;
        quotaConcurrentActive.value = Math.min(10, quotaConcurrentActive.value + 2);
        quotaDailyConsumed.value = Math.min(quotaDailyLimit.value, quotaDailyConsumed.value + tokens);
        quotaHourlyConsumed.value += tokens;
        quotaProjectRequests.value += 4;
        
        realTimeTelemetry.value.unshift({
          id: Date.now(),
          event: 'QuotaUsageUpdated',
          tokens: tokens,
          time: 'Just now'
        });
        if (realTimeTelemetry.value.length > 4) realTimeTelemetry.value.pop();

        setTimeout(() => {
          quotaConcurrentActive.value = Math.max(2, quotaConcurrentActive.value - 2);
        }, 400);

        if (count >= 5) {
          clearInterval(interval);
          drainInProgress.value = false;
        }
      }, 450);
    }

    let quotaInterval = null;

    const livePages = ref([
      { path: '/dashboard/analytics', readers: 42 },
      { path: '/docs/fluent-queries', readers: 35 },
      { path: '/pricing', readers: 28 },
      { path: '/checkout/success', readers: 19 },
      { path: '/blog/laravel-ga4-guide', readers: 14 }
    ]);

    const liveCountries = ref([
      { country: 'United States', users: 64, pct: 85 },
      { country: 'United Kingdom', users: 31, pct: 50 },
      { country: 'Germany', users: 22, pct: 36 },
      { country: 'Canada', users: 16, pct: 28 },
      { country: 'Australia', users: 15, pct: 25 }
    ]);

    // Live E-Commerce Funnel Pipeline Data
    const liveFunnel = ref({
      name: 'E-Commerce Purchase Funnel',
      totalEntries: 14850,
      totalCompletions: 1240,
      overallConversionRate: 8.35,
      steps: [
        { step: 1, name: '1. View Item', eventName: 'view_item', count: 14850, dropOffCount: 0, dropOffRate: 0.0, conversionRate: 100.0, overallConversionRate: 100.0 },
        { step: 2, name: '2. Add to Cart', eventName: 'add_to_cart', count: 5720, dropOffCount: 9130, dropOffRate: 61.48, conversionRate: 38.52, overallConversionRate: 38.52 },
        { step: 3, name: '3. Begin Checkout', eventName: 'begin_checkout', count: 2410, dropOffCount: 3310, dropOffRate: 57.87, conversionRate: 42.13, overallConversionRate: 16.23 },
        { step: 4, name: '4. Purchase', eventName: 'purchase', count: 1240, dropOffCount: 1170, dropOffRate: 48.55, conversionRate: 51.45, overallConversionRate: 8.35 }
      ]
    });

    const topProductsList = ref([
      { id: 'SKU-ENT-01', name: 'Genlytics Enterprise Pro', category: 'Software / SaaS', revenue: 14250.00, itemsPurchased: 95, itemsViewed: 1820, cartToViewRate: 24.2 },
      { id: 'SKU-DEV-02', name: 'Developer Team Pass', category: 'Software / SaaS', revenue: 8890.00, itemsPurchased: 148, itemsViewed: 2450, cartToViewRate: 18.5 },
      { id: 'SKU-SLA-03', name: 'Priority 24/7 SLA Support', category: 'Support Services', revenue: 6400.00, itemsPurchased: 32, itemsViewed: 620, cartToViewRate: 12.1 },
      { id: 'SKU-CSL-04', name: 'Architecture & GA4 Audit', category: 'Consulting', revenue: 4950.00, itemsPurchased: 11, itemsViewed: 410, cartToViewRate: 9.8 }
    ]);

    const topCategoriesList = ref([
      { category: 'Software / SaaS', revenue: 23140.00, units: 243, pct: 100 },
      { category: 'Support Services', revenue: 6400.00, units: 32, pct: 28 },
      { category: 'Consulting', revenue: 4950.00, units: 11, pct: 21 },
      { category: 'Add-on Plugins', revenue: 2180.00, units: 84, pct: 10 }
    ]);

    // Live Simulation Tick
    let simInterval = null;
    const startSimulation = () => {
      simInterval = setInterval(() => {
        // Jitter active users naturally
        const delta = Math.floor(Math.random() * 5) - 2;
        liveStats.value.activeUsers = Math.max(120, liveStats.value.activeUsers + delta);
        liveStats.value.views += Math.floor(Math.random() * 4) + 1;
        liveStats.value.sessions += Math.random() > 0.6 ? 1 : 0;
      }, 2500);
    };

    // Available Dimensions and Metrics for Interactive Query Builder
    const availableDimensions = [
      'country', 'city', 'pagePath', 'pageTitle', 
      'sessionSource', 'sessionMedium', 'deviceCategory', 'date'
    ];

    const availableMetrics = [
      'activeUsers', 'sessions', 'screenPageViews', 'conversions', 
      'bounceRate', 'eventCount'
    ];

    // Fluent Builder State
    const fluentBuilder = ref({
      period: 'last7Days',
      dimensions: ['country', 'city'],
      metrics: ['activeUsers', 'sessions'],
      limit: 10,
      cacheTtl: 'default'
    });

    const helperOptions = [
      { name: 'ecommerceFunnel', desc: 'Default GA4 e-commerce purchase pipeline with drop-offs' },
      { name: 'topProducts', desc: 'Top performing products by revenue, views & purchases' },
      { name: 'topCategories', desc: 'Top product categories by revenue & volume' },
      { name: 'topPages', desc: 'Top pages by views and users' },
      { name: 'topCountries', desc: 'Geographic traffic by country' },
      { name: 'trafficSources', desc: 'Acquisition channels and mediums' },
      { name: 'userEngagement', desc: 'Engagement rate and duration' },
      { name: 'deviceCategories', desc: 'Mobile vs desktop breakdown' },
      { name: 'conversions', desc: 'Events and conversions by name' },
      { name: 'realtimeActiveUsers', desc: 'Active users in the last 30 minutes' }
    ];

    const dedicatedQueries = [
      {
        name: 'ecommerceFunnel',
        title: 'E-Commerce Funnel Pipeline',
        icon: 'git-merge',
        description: 'Sequential conversion pipeline (view_item -> add_to_cart -> checkout -> purchase) with drop-off rates.',
        code: 'Genlytics::ecommerceFunnel(period: "30daysAgo");'
      },
      {
        name: 'topProducts',
        title: 'Top Performing Products',
        icon: 'shopping-bag',
        description: 'Ranks products by gross item revenue, units purchased, views, and cart-to-view rate.',
        code: 'Genlytics::topProducts(limit: 10);'
      },
      {
        name: 'topCategories',
        title: 'Top Performing Categories',
        icon: 'layers',
        description: 'Analyzes revenue and volume breakdown across store categories with DTO hydration.',
        code: 'Genlytics::topCategories(limit: 5);'
      },
      {
        name: 'topPages',
        title: 'Top Viewed Pages',
        icon: 'file-text',
        description: 'Retrieves most visited URLs with screen views and average session duration.',
        code: 'Genlytics::topPages(limit: 10);'
      },
      {
        name: 'topCountries',
        title: 'Geographic Traffic',
        icon: 'globe',
        description: 'Ranks top visitor countries with active user counts and sessions.',
        code: 'Genlytics::topCountries(limit: 10);'
      },
      {
        name: 'trafficSources',
        title: 'Acquisition Channels',
        icon: 'trending-up',
        description: 'Analyzes sessions by sessionSource and sessionMedium with bounce rates.',
        code: 'Genlytics::trafficSources(limit: 10);'
      },
      {
        name: 'userEngagement',
        title: 'User Engagement',
        icon: 'clock',
        description: 'Overview of engaged sessions, engagement rate, and active users.',
        code: 'Genlytics::userEngagement();'
      },
      {
        name: 'deviceCategories',
        title: 'Device Breakdown',
        icon: 'smartphone',
        description: 'Mobile, desktop, and tablet distribution for responsive design insights.',
        code: 'Genlytics::deviceCategories();'
      },
      {
        name: 'realtimeActiveUsers',
        title: 'Live Real-Time Users',
        icon: 'radio',
        description: 'Single-call active user telemetry across the previous 30 minutes.',
        code: 'Genlytics::realtimeActiveUsers();'
      }
    ];

    const toggleFluentDim = (dim) => {
      const idx = fluentBuilder.value.dimensions.indexOf(dim);
      if (idx > -1) {
        fluentBuilder.value.dimensions.splice(idx, 1);
      } else {
        fluentBuilder.value.dimensions.push(dim);
      }
    };

    const toggleFluentMet = (met) => {
      const idx = fluentBuilder.value.metrics.indexOf(met);
      if (idx > -1) {
        fluentBuilder.value.metrics.splice(idx, 1);
      } else {
        fluentBuilder.value.metrics.push(met);
      }
    };

    const studioGeneratedCode = computed(() => {
      if (studioTab.value === 'fluent') {
        const dims = JSON.stringify(fluentBuilder.value.dimensions).replace(/"/g, "'");
        const mets = JSON.stringify(fluentBuilder.value.metrics).replace(/"/g, "'");
        const limit = fluentBuilder.value.limit;

        let cacheChain = '';
        if (fluentBuilder.value.cacheTtl === 'remember_7200') {
          cacheChain = '\\n    ->remember(7200) // Cache for 2 hours';
        } else if (fluentBuilder.value.cacheTtl === 'cacheFor_interval') {
          cacheChain = '\\n    ->cacheFor(new \\\\DateInterval(\\'PT30M\\')) // 30 mins TTL';
        } else if (fluentBuilder.value.cacheTtl === 'dontCache') {
          cacheChain = '\\n    ->dontCache() // Bypass cache & fetch live data';
        }

        return \`use zfhassaan\\\\genlytics\\\\facades\\\\AnalyticsFacade as Genlytics;

// Eloquent-Style Fluent Chaining
\\$analytics = Genlytics::query()
    ->\${fluentBuilder.value.period}()
    ->dimensions(\${dims})
    ->metrics(\${mets})\${cacheChain}
    ->orderBy('\${fluentBuilder.value.metrics[0] || 'activeUsers'}', 'DESC')
    ->limit(\${limit})
    ->get(); // Returns Illuminate\\\\Support\\\\Collection

// Use standard Collection methods
\\$topRow = \\$analytics->first();\`;
      }

      if (studioTab.value === 'helpers') {
        if (selectedHelper.value === 'ecommerceFunnel') {
          return \`use zfhassaan\\\\genlytics\\\\facades\\\\AnalyticsFacade as Genlytics;

// E-Commerce Purchase Funnel with Drop-Off Analysis
\\$funnel = Genlytics::ecommerceFunnel('30daysAgo');

echo "Funnel: " . \\$funnel->name . " | Conversion: " . \\$funnel->overallConversionRate . "%\\n";

foreach (\\$funnel->steps as \\$step) {
    echo "Step {\$step->step}: {\$step->name} -> Count: {\$step->count} (Drop-off: {\$step->dropOffRate}%)\\n";
}\`;
        }

        if (selectedHelper.value === 'topProducts') {
          return \`use zfhassaan\\\\genlytics\\\\facades\\\\AnalyticsFacade as Genlytics;

// Top Performing Products Hydrated as Strongly-Typed DTOs
\\$products = Genlytics::topProducts(limit: 10, period: '30daysAgo');

foreach (\\$products as \\$product) {
    echo "{\\$product->name} [{\\$product->category}]: \\\${\\$product->revenue} ({\\$product->itemsPurchased} sold)\\n";
}\`;
        }

        if (selectedHelper.value === 'topCategories') {
          return \`use zfhassaan\\\\genlytics\\\\facades\\\\AnalyticsFacade as Genlytics;

// Top Performing Product Categories by Revenue
\\$categories = Genlytics::topCategories(limit: 5, period: '30daysAgo');

foreach (\\$categories as \\$category) {
    echo "{\\$category->category}: \\\${\\$category->revenue} ({\\$category->cartToViewRate}% cart-to-view)\\n";
}\`;
        }

        if (selectedHelper.value === 'topPages') {
          return \`use zfhassaan\\\\genlytics\\\\facades\\\\AnalyticsFacade as Genlytics;

// Dedicated Helper: Top Viewed Pages
\\$pages = Genlytics::topPages(limit: 10, period: '30daysAgo');

foreach (\\$pages as \\$page) {
    echo \\$page['dimensions']['pagePath'] . ': ' . \\$page['metrics']['screenPageViews'] . ' views';
}\`;
        }

        if (selectedHelper.value === 'topCountries') {
          return \`use zfhassaan\\\\genlytics\\\\facades\\\\AnalyticsFacade as Genlytics;

// Dedicated Helper: Top Geographic Countries
\\$countries = Genlytics::topCountries(limit: 10);

// Returns Collection: [['dimensions' => ['country' => 'United States'], 'metrics' => [...]]]\`;
        }

        if (selectedHelper.value === 'realtimeActiveUsers') {
          return \`use zfhassaan\\\\genlytics\\\\facades\\\\AnalyticsFacade as Genlytics;

// Dedicated Helper: Active Users Right Now (last 30 minutes)
\\$liveCount = Genlytics::realtimeActiveUsers(); // returns int (e.g. 148)\`;
        }

        return \`use zfhassaan\\\\genlytics\\\\facades\\\\AnalyticsFacade as Genlytics;

// Dedicated Helper: \${selectedHelper.value}
\\$data = Genlytics::\${selectedHelper.value}();\`;
      }

      // Batch Tab
      return \`use zfhassaan\\\\genlytics\\\\facades\\\\AnalyticsFacade as Genlytics;

// Multi-Widget Batch Query in 1 Single HTTP Request (80%+ faster)
\\$dashboard = Genlytics::getBatchReports([
    'traffic_sources' => [
        'dateRange'  => ['start_date' => '30daysAgo', 'end_date' => 'today'],
        'dimensions' => ['sessionSource'],
        'metrics'    => ['sessions', 'activeUsers'],
        'options'    => ['limit' => 10],
    ],
    'top_pages' => [
        'dateRange'  => ['start_date' => '30daysAgo', 'end_date' => 'today'],
        'dimensions' => ['pagePath'],
        'metrics'    => ['screenPageViews'],
        'options'    => ['limit' => 10],
    ],
    'geo_distribution' => [
        'dateRange'  => ['start_date' => '30daysAgo', 'end_date' => 'today'],
        'dimensions' => ['country'],
        'metrics'    => ['activeUsers'],
        'options'    => ['limit' => 10],
    ],
]);\`;
    });

    const isDocPage = (page) => {
      if (!page || typeof page !== 'string') return false;
      return Object.prototype.hasOwnProperty.call(docsContent, page) || [
        'getting-started',
        'fluent-queries',
        'ecommerce-and-funnels',
        'quota-and-limits',
        'dtos-and-resources',
        'architecture',
        'configuration',
        'api-reference',
        'batch-reporting',
        'faq'
      ].includes(page);
    };

    const refreshLucideIcons = () => {
      nextTick(() => {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
          window.lucide.createIcons();
        }
      });
    };

    const navigateTo = (page) => {
      if (!page) return;
      isMobileMenuOpen.value = false;
      isMobileDocsDrawerOpen.value = false;
      try { document.body.style.overflow = ''; } catch (e) {}
      currentPage.value = page;
      if (isDocPage(page)) {
        lastDocPage.value = page;
      }
      window.location.hash = page;
      try {
        localStorage.setItem('genlytics_current_page', page);
      } catch (e) {}
      window.scrollTo({ top: 0, behavior: 'smooth' });
      highlightCode();
      refreshLucideIcons();
    };

    const handleHashChange = () => {
      const hash = (window.location.hash || '').replace(/^#+/, '').trim();
      if (hash && (hash === 'home' || isDocPage(hash))) {
        if (currentPage.value !== hash) {
          currentPage.value = hash;
          if (isDocPage(hash)) {
            lastDocPage.value = hash;
          }
          try {
            localStorage.setItem('genlytics_current_page', hash);
          } catch (e) {}
          highlightCode();
          refreshLucideIcons();
        }
      }
    };

    const toggleTheme = () => {
      isDarkMode.value = !isDarkMode.value;
      const theme = isDarkMode.value ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', theme);
      if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
      localStorage.setItem('genlytics_theme', theme);
      refreshLucideIcons();
    };

    const switchStudioTab = (tab) => {
      studioTab.value = tab;
      refreshLucideIcons();
    };

    const copyText = (text) => {
      navigator.clipboard.writeText(text).then(() => {
        copiedText.value = text;
        refreshLucideIcons();
        setTimeout(() => {
          copiedText.value = '';
          refreshLucideIcons();
        }, 2000);
      });
    };

    const renderedMarkdown = computed(() => {
      if (!isDocPage(currentPage.value)) return '';
      const raw = docsContent[currentPage.value] || '# Page Not Found';
      nextTick(() => {
        highlightCode();
        refreshLucideIcons();
      });
      return marked.parse(raw);
    });

    const highlightCode = () => {
      nextTick(() => {
        if (window.Prism) {
          Prism.highlightAll();
        }
      });
    };

    onMounted(() => {
      // Restore theme
      const savedTheme = localStorage.getItem('genlytics_theme');
      if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDarkMode.value = true;
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.classList.remove('dark');
      }

      // Check URL hash and sustain route across refreshes
      let targetRoute = 'home';
      const hash = (window.location.hash || '').replace(/^#+/, '').trim();
      if (hash && (hash === 'home' || isDocPage(hash))) {
        targetRoute = hash;
      } else {
        const saved = localStorage.getItem('genlytics_current_page');
        if (saved && (saved === 'home' || isDocPage(saved))) {
          targetRoute = saved;
        }
      }

      currentPage.value = targetRoute;
      if (isDocPage(targetRoute)) {
        lastDocPage.value = targetRoute;
      }
      window.location.hash = targetRoute;

      window.addEventListener('hashchange', handleHashChange);

      const handleGlobalKeydown = (e) => {
        if ((e.key === 'k' && (e.metaKey || e.ctrlKey)) || (e.key === '/' && !isSearchModalOpen.value && document.activeElement && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA')) {
          e.preventDefault();
          openSearchModal();
          return;
        }

        if (e.key === 'Escape') {
          if (isSearchModalOpen.value) {
            e.preventDefault();
            closeSearchModal();
            return;
          }
          if (isMobileMenuOpen.value) {
            e.preventDefault();
            closeMobileMenu();
            return;
          }
          if (isMobileDocsDrawerOpen.value) {
            e.preventDefault();
            closeMobileDocsDrawer();
            return;
          }
        }
      };

      window.addEventListener('keydown', handleGlobalKeydown);

      startSimulation();
      updatePstCountdown();
      quotaInterval = setInterval(updatePstCountdown, 1000);
      highlightCode();
      refreshLucideIcons();
    });

    onUnmounted(() => {
      window.removeEventListener('hashchange', handleHashChange);
      if (typeof handleGlobalKeydown !== 'undefined') {
        window.removeEventListener('keydown', handleGlobalKeydown);
      }
      if (simInterval) clearInterval(simInterval);
      if (quotaInterval) clearInterval(quotaInterval);
    });

    return {
      currentPage,
      lastDocPage,
      isDarkMode,
      docsSearch,
      isSearchModalOpen,
      selectedSearchIndex,
      modalSearchInputRef,
      popularSearchTags,
      quickJumpDocs,
      openSearchModal,
      closeSearchModal,
      setSearchQuery,
      navigateSearchResults,
      selectActiveSearchResult,
      navigateToDoc,
      docNavSections,
      searchResults,
      selectSearchResult,
      onSearchEnter,
      copiedText,
      studioTab,
      selectedHelper,
      batchPreset,
      liveStats,
      livePages,
      liveCountries,
      liveFunnel,
      topProductsList,
      topCategoriesList,
      availableDimensions,
      availableMetrics,
      fluentBuilder,
      helperOptions,
      dedicatedQueries,
      toggleFluentDim,
      toggleFluentMet,
      studioGeneratedCode,
      isDocPage,
      navigateTo,
      toggleTheme,
      switchStudioTab,
      refreshLucideIcons,
      copyText,
      renderedMarkdown,
      quotaPropertyType,
      quotaMode,
      quotaDailyConsumed,
      quotaHourlyConsumed,
      quotaConcurrentActive,
      quotaProjectRequests,
      quotaPstCountdown,
      quotaPacingStatus,
      quotaDailyLimit,
      quotaDailyRemaining,
      quotaDailyPercent,
      quotaHourlyLimit,
      quotaHourlyRemaining,
      quotaHourlyPercent,
      quotaWarningThreshold,
      quotaPacingBadge,
      quotaUserLevelLimit,
      realTimeTelemetry,
      drainInProgress,
      simulateQuotaExecution,
      simulateDrainDailyQuota,
      isMobileMenuOpen,
      isMobileDocsDrawerOpen,
      toggleMobileMenu,
      closeMobileMenu,
      toggleMobileDocsDrawer,
      closeMobileDocsDrawer,
      getCurrentDocTitle,
      getCurrentDocGroup,
      packageVersion
    };
  }
});

app.mount('#app');
`;

// Write app.js to website and docs
fs.writeFileSync(path.join(websiteDir, 'app.js'), appJsContent);
fs.writeFileSync(path.join(docsDir, 'app.js'), appJsContent);
console.log('  ✓ Generated: app.js');

// Copy index.html and style.css to docs with version interpolation
let indexHtml = fs.readFileSync(path.join(websiteDir, 'index.html'), 'utf8');
indexHtml = indexHtml.split('{{ packageVersion }}').join(packageVersion);
indexHtml = indexHtml.split('{{packageVersion}}').join(packageVersion);
indexHtml = indexHtml.split('{{VERSION}}').join(packageVersion);
fs.writeFileSync(path.join(docsDir, 'index.html'), indexHtml);
console.log('  ✓ Processed & Copied: index.html -> docs/index.html');

fs.copyFileSync(path.join(websiteDir, 'style.css'), path.join(docsDir, 'style.css'));
console.log('  ✓ Copied: style.css -> docs/style.css');

// Copy logo assets to docs
const logoFiles = ['logo.svg', 'logo-icon.svg', 'logo.png', 'logo-icon.png', 'logo-light.png', 'logo-icon-light.png'];
for (const file of logoFiles) {
  const srcPath = path.join(websiteDir, file);
  if (fs.existsSync(srcPath)) {
    fs.copyFileSync(srcPath, path.join(docsDir, file));
    console.log(`  ✓ Copied: ${file} -> docs/${file}`);
  }
}

// Remove banner.jpeg if present in docs
if (fs.existsSync(path.join(docsDir, 'banner.jpeg'))) {
  fs.unlinkSync(path.join(docsDir, 'banner.jpeg'));
  console.log('  ✓ Removed legacy banner.jpeg from docs');
}

// Write .nojekyll for GitHub Pages
fs.writeFileSync(path.join(docsDir, '.nojekyll'), '');
console.log('  ✓ Generated: docs/.nojekyll');

console.log('\\nSuccessfully built Genlytics documentation in "docs/" directory!');
