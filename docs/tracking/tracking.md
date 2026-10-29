# Live Bajar Tracking & Intelligence: পূর্ণ ব্যবহার ও সেটআপ গাইড

এই গাইডে **/admin/tracking**-এর সব ২২টি menu, প্রতিটি setting, event, provider route, queue, privacy control, dashboard metric এবং বর্তমান সীমাবদ্ধতা ব্যাখ্যা করা হয়েছে। UI-তে কোনো menu দেখা গেলেই সেটি পূর্ণাঙ্গ feature ধরে নেবেন না: যেগুলো এখনো বানানো হয়নি সেগুলো আলাদা করে চিহ্নিত করা আছে।

## এক নজরে

- Admin panel: **/admin/tracking**; বর্তমান admin login-ই ব্যবহার হয়।
- প্রথম-পক্ষ event collection, provider route, browser tag এবং AI report সব default-এ বন্ধ; consent required এবং Test Mode চালু।
- Canonical tracking event ৯টি: **PageView, ViewContent, AddToCart, InitiateCheckout, Purchase, LinkClick, PhoneClick, EmailClick, Search**।
- এই module-এর server Purchase delivery সমর্থিত Meta Conversions API, TikTok Events API 2.0 এবং GA4 Measurement Protocol-এ। Browser tracking-এ Meta, TikTok, GA4 ও Google Ads আছে।
- TikTok-এর server Events API Test Mode-এ ইচ্ছাকৃতভাবে blocked। Google Ads server upload, Visual Trigger Builder, recommendation workflow এবং Smart Alert delivery implement করা হয়নি।
- “Accepted” provider request-এর response; এটি event validation, attribution, match quality score বা revenue/ROI প্রমাণ করে না।

## ১. নিরাপদভাবে চালু করার ধাপ

### ১.১ নতুন environment-এ database migration

আগে database backup নিন। Tracking tables এবং order-এর idempotency IDs তৈরি করতে project root-এ চালান:

    php artisan migrate

Production-এ:

    php artisan migrate --force

Migration না থাকলে **/admin/tracking** 503 দেখাবে। বর্তমান migration **tracking_configurations, tracking_events, tracking_deliveries, tracking_reports** এবং dedicated **tracking_jobs** table তৈরি করে; order-এ checkout/purchase deduplication ID-ও যোগ করে। অতিরিক্ত Laravel **queue:table** migration চালাবেন না—এই module-এর database queue **tracking_jobs** ব্যবহার করে।

### ১.২ Background queue ও scheduler

Project-এর **.env**-এ **QUEUE_CONNECTION=database** দিন। .env-এ যদি **sync** থাকে, সেটি config-এর database default-কে override করবে। এরপর চালান:

    php artisan config:clear
    php artisan queue:work database --queue=tracking,default --tries=5 --timeout=20

Worker-টি আলাদা terminal/process-এ চলতে হবে। Windows/XAMPP local development-এ আরেকটি terminal-এ scheduler চালাতে পারেন:

    php artisan schedule:work

Production cron প্রতি মিনিটে Laravel scheduler চালাবে:

    * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1

Worker provider delivery ও AI report চালায়। Scheduler প্রতিদিন 03:40-এ retention সময় পার হওয়া event মুছে। Queue বা worker না থাকলে server delivery **blocked_queue** হবে এবং AI report তৈরি হবে না; sync request থেকে provider-কে সরাসরি call করা হয় না।

### ১.৩ Consent, collection ও retention

Configuration / Setup Wizard-এ গিয়ে প্রথমে:

1. **Require the storefront consent grant** চালু রাখুন।
2. **Event retention** ৩০–৩৬৫ দিনের মধ্যে policy অনুযায়ী ঠিক করুন (default ৯০)।
3. **Enable first-party event collection** চালু করুন।
4. Test Mode চালু রাখুন; provider credentials ও route প্রস্তুত হওয়ার আগে Production Mode-এ যাবেন না।
5. **Allow consented, SHA-256 hashed checkout email/phone for server matching** কেবল consent, privacy notice এবং applicable policy অনুমতি দিলে চালু করুন।
6. Save করুন। Save form নিজে কোনো provider-কে contact করে না।

Consent required থাকলে এবং existing optional tracking বা নতুন first-party/direct tracking চালু থাকলে storefront-এ প্রথমবার allow/reject panel দেখায়। Allow করলে ১৮০ দিনের consent cookie-তে grant থাকে; reject করলে browser tracking বন্ধ হয়, known analytics/ad cookies clear হয় এবং page reload হয়। Consent prompt/setting-টি basic preference control; পূর্ণ CMP বা legal review নয়। Site owner-কে notice text, jurisdiction, consent wording ও retention নিজে যাচাই করতে হবে।

### ১.৪ Provider সেটআপের ক্রম

Provider account-এ credential তৈরি করে panel-এ সঠিক ID/secret বসান। Server secret password field-এ রাখুন; browser ID public হতে পারে, access token/API secret/API key public নয়। Secrets encrypted অবস্থায় database-এ থাকে এবং blank রেখে Save করলে আগের secret রাখা হয়। সংশ্লিষ্ট **Clear saved token/secret/API key** checkbox দিলে তবেই সেটি মুছে যায়। **APP_KEY** হারালে encrypted credential আর পড়া যাবে না; key স্থিতিশীল ও backup-এ সুরক্ষিত রাখুন।

| Provider | আবশ্যক field | Channel ও বাস্তব সীমা |
|---|---|---|
| Meta Pixel + Conversions API | Pixel ID; server-এর জন্য CAPI access token ও currently supported format-এর Graph API version (**vNN.0**); Test Mode-এ server চালালে Test Events code | Browser Pixel ও queued server CAPI আলাদা toggle/route। Test Mode-এ browser tag বন্ধ; Meta server event Test Events code পেলে test event হিসেবে পাঠায়। |
| TikTok Pixel + Events API 2.0 | Pixel ID; server-এর জন্য Events API access token এবং consented matching data | Browser Pixel ও server API আলাদা। Test Mode-এ browser tag বন্ধ এবং server Events API ইচ্ছাকৃতভাবে blocked, কারণ বর্তমান API 2.0 schema-র test payload field এখানে verified নয়। |
| Google Analytics 4 | Measurement ID (**G-...**); server-এর জন্য Measurement Protocol API secret | Browser tag-এর জন্য Measurement ID যথেষ্ট। Server delivery-তে consented GA client ID লাগে। HTTP success validation নয়। |
| Google Ads | Conversion ID এবং Conversion label | শুধু browser Purchase conversion tag আছে। OAuth/Conversion Action server upload নেই। |
| OpenAI business reports | API key; Responses API structured output support করে এমন model | Optional admin-initiated AI; async queue আবশ্যক। Default model **gpt-4.1-mini**; report/day ০–৫, output token ১০০–৩০০০। |

Meta Graph version ঘন ঘন বদলায়। UI placeholder **vNN.0**; Meta-এর version support তালিকা দেখে বর্তমানে supported version লিখুন। পুরনো version ব্যবহার করলে API call fail হতে পারে। Token/API key কোনো documentation, screenshot, CSV বা source control-এ লিখবেন না।

### ১.৫ Test থেকে Production

Staging domain-এ শুরু করুন। Consent grant দিয়ে store-এর একটি page/product দেখুন, Add to Cart করুন, valid cart থেকে checkout শুরু করুন এবং প্রয়োজনে controlled test order complete করুন। Dashboard, Event Management/Inspector, Delivery Logs এবং provider-side Test Events/diagnostics—সব মিলিয়ে যাচাই করুন।

- Browser provider tags Test Mode-এ পাঠানো হয় না; তাই browser tag যাচাই করতে staging-এ setting-গুলো সেভ করে Test Mode বন্ধ করতে হবে। Staging credential/test property ব্যবহার করুন।
- Meta server Test Mode-এ Test Events code ব্যবহার করতে পারে।
- TikTok server Test Mode-এ পাঠানো যায় না। Production Mode কখনোই শুধু পরীক্ষার জন্য live customer traffic-এ চালু করবেন না।
- Cloudflare Zaraz বা অন্য integration-এ একই Pixel/GA/TikTok destination থাকলে direct tag চালুর আগে সেটি পরীক্ষা করুন—একই event দুবার গণনা হতে পারে।
- Purchase browser/server dual route চালু করার আগে provider-এ একই event name ও একই event ID দিয়ে deduplication হচ্ছে নিশ্চিত করুন।
- সব ID, consent, worker ও route ঠিক হলে তবেই production domain-এ Test Mode বন্ধ এবং প্রযোজ্য provider routes চালু করুন।

## ২. Configuration form-এর field ও checkbox কী করে

Configuration / Setup Wizard, Platform & Pixel Management, Event Routing Matrix, Browser Tracking, Server Tracking এবং Consent & Privacy menu-গুলো মূলত একই configuration form-এ নিয়ে যায়। Menu আলাদা হলেও settings save করার form একটাই।

### কেন menu আলাদা, এবং আলাদা করে configure করতে হবে কি?

না—এই পাঁচটি menu-তে আলাদা আলাদা configuration record নেই। এগুলো একই settings form-এর navigation shortcut; একটি menu খুলে field বদলে একবার **Save tracking configuration** করলেই setting সেভ হয়, অন্য menu-তে গেলে সেটিই দেখা যায়। **Configuration / Setup Wizard** ও **Platform & Pixel Management**-এ অতিরিক্ত Safe activation checklist দেখা যায়। **Server Tracking**-এ একই form-এর সঙ্গে delivery log-ও দেখা যায়। অন্য menu label মূলত কোন কাজের অংশ তা বুঝতে সাহায্য করে; বর্তমান UI form-টিকে ওই অংশে filter করে না।

| Menu | আলাদা menu-র ধারণাগত উদ্দেশ্য | বাস্তবে এখানে কী করবেন | আলাদা Save? |
|---|---|---|---|
| **Configuration / Setup Wizard** | Collection, consent, retention, provider ও route প্রস্তুতির সার্বিক checklist | প্রথমবার সব master setting একসঙ্গে সাজিয়ে নিন | না—একই form |
| **Platform & Pixel Management** | Meta, TikTok, GA4, Google Ads ও OpenAI destination-এর ID, secret এবং enable toggle | যেসব platform ব্যবহার করবেন তাদের credential বসান ও সংশ্লিষ্ট channel enable করুন | না—একই form |
| **Event Routing Matrix** | প্রতিটি event কোন platform/channel-এ যাবে তার নির্বাচন | নিচের matrix-এ প্রয়োজনীয় event ও destination checkbox দিন | না—একই form |
| **Browser Tracking** | Storefront visitor-এর browser tag ও browser event route | Public Pixel/Measurement/Conversion ID, browser toggle এবং browser route ঠিক করুন | না—একই form |
| **Server Tracking** | Backend event, server API এবং queued delivery | Server credential/toggle/route ঠিক করুন; পাশাপাশি delivery status দেখুন | না—একই form; delivery log আলাদা read-only অংশ |

**একবার configure করার প্রস্তাবিত ক্রম:** (১) Setup Wizard অংশে collection, consent ও retention; (২) Platforms অংশে শুধু প্রয়োজনীয় provider ID/secret; (৩) Routing Matrix-এ ঠিক যে event পাঠাতে চান শুধু সেই route; (৪) server route হলে asynchronous queue worker; (৫) form-এর শেষে একবার Save। পরে যেকোনো তালিকাভুক্ত menu খুলে একই form edit ও Save করতে পারবেন।

**Browser আর server-এর উপকার আলাদা:** Browser channel store page, product view, cart, checkout ও link action-এর মতো visitor-side event ধরে এবং provider Pixel/gtag-এ পাঠাতে পারে। এটি consent, JavaScript/browser availability ও Test Mode-এর ওপর নির্ভরশীল। Server channel backend-এ সফল order commit হওয়ার পরের Purchase পাঠায়; worker/queue থাকায় provider request checkout web request-এর মধ্যে চালাতে হয় না। এই module-এ server route matrix-এ সব event দেখালেও server event বাস্তবে শুধু verified Purchase তৈরি হয়। Meta/TikTok server matching-এ consented checkout matching data দরকার; GA4-এ consented client ID দরকার। কোনো channel-ই collection, consent, route ও provider setup-এর অন্য gate এড়িয়ে যায় না।

### Collection and privacy

- **Enable first-party event collection**: app-এর নিজস্ব **/tracking/events** endpoint-এ allowlisted browser event সংরক্ষণ চালু করে।
- **Require the storefront consent grant**: grant না থাকলে নতুন event ও external/browser tracking আটকে দেয়। Default on।
- **Keep external destinations in test mode**: browser SDK/tag বন্ধ রাখে; Meta server request-এ Test Events code যোগ হয়; GA4 server request debug mode পায়; TikTok server blocked থাকে।
- **Allow consented, SHA-256 hashed checkout email/phone for server matching**: consent grant থাকা order Purchase-এর email trim/lowercase করে এবং phone থেকে non-digit বাদ দিয়ে SHA-256 hash তৈরি করে; encrypted at-rest হিসেবে সংরক্ষণ করে; server provider route-এ পাঠায়। Phone-এ country code নিজে থেকে যোগ হয় না। এটি শুধু server-generated Purchase-এ কাজে লাগে, প্রতিটি browser event-এ নয়।
- **Event retention (days)**: ৩০–৩৬৫ দিনের event-retention window; scheduler সক্রিয় থাকলে প্রতিদিন expired events এবং related delivery rows মুছে।

### Per-event routing matrix

প্রতিটি ৯টি event-এর জন্য আলাদা checkbox: **Meta browser**, **Meta server**, **TikTok browser**, **TikTok server**, **GA4 browser**, **GA4 server**, **Google Ads browser**। সব checkbox default-এ off।

Checkbox মানে শুধু ঐ event/destination/channel অনুমোদন; credential, provider enable toggle, collection, consent, Test Mode এবং queue readiness-ও pass করতে হবে। Browser ও server আলাদা channel। Server matrix-এ সব event-এর row থাকলেও বর্তমান verified backend-generated server event শুধু Purchase; অন্য event-এর server delivery record তৈরি হয় না। Google Ads server control UI-তে disabled।

## ৩. Admin panel-এর ২২টি menu

| # | Menu | এখন কী দেখা/করা যায় | কাজে লাগে / সীমাবদ্ধতা |
|---:|---|---|---|
| 1 | **Overview Dashboard** | Event row, unique event ID, duplicate copies, order, pending/cancelled, gross order value, funnel, link, campaign, queue/data-quality snapshot | পুরো সময়সীমার দ্রুত সারাংশ। Order figures বিদ্যমান orders table থেকে আসে; payment settlement নয়। |
| 2 | **Configuration / Setup Wizard** | Safe activation checklist, collection/consent/retention, provider credentials/toggles, AI controls, routing matrix | সব tracking setting এক জায়গায় সেট করে। Save করলে credentials server-side encrypted থাকে। |
| 3 | **Platform & Pixel Management** | Meta, TikTok, GA4, Google Ads ও OpenAI-এর ID/secret/toggle fields | Provider destination প্রস্তুত করে। Browser ও server credential আলাদা; field পূরণ হলেই event পাঠানো শুরু হয় না—routing ও collection gates-ও লাগে। |
| 4 | **Event Management** | সাম্প্রতিক local event rows: time, event, source, event ID, consent, path এবং delivery record | Store থেকে কোন allowlisted event app গ্রহণ করেছে তা পরীক্ষা করুন। Pagination ২৫ row। |
| 5 | **Visual Trigger Builder** | **NOT_YET_IMPLEMENTED** notice | CSS selector দিয়ে নতুন event rule বানানো, publish/version/rollback এখনো নেই। কেবল নির্দিষ্ট built-in storefront hooks কাজ করে। |
| 6 | **Event Routing Matrix** | Per-event × provider/channel checkbox matrix | কোন event কোন browser/server destination-এ যাবে তা নিয়ন্ত্রণ করে। সব route শুরুতে বন্ধ। |
| 7 | **Browser Tracking** | একই config form-এ browser provider toggles ও browser route matrix | Visitor browser থেকে provider Pixel/gtag event পাঠায়। Consent, collection, route এবং Test Mode gate মানে; provider ID browser-এ দেখা যায়। |
| 8 | **Server Tracking** | একই config form; সঙ্গে server delivery rows | Worker থেকে Meta CAPI, TikTok Events API 2.0, GA4 Measurement Protocol। বর্তমানে backend Purchase-ই তৈরি হয়; Google Ads server upload নেই। |
| 9 | **Live Event Inspector** | Event Management-এর মতো একই recent local-event table | “Live” নাম থাকলেও websocket/real-time stream নয়; page refresh/pagination করে local events দেখুন। |
| 10 | **Analytics Dashboard** | KPI এবং observed funnel progress bars | ৭/৩০/৯০ দিনের স্থানীয় aggregate; custom date range বা platform attribution report নেই। |
| 11 | **Conversion Funnels** | PageView → ViewContent → AddToCart → InitiateCheckout → Purchase-এর আলাদা unique-ID count | ধাপের observed volume বোঝায়। একই ব্যবহারকারীর cohort path বা step-to-step conversion rate নয়। |
| 12 | **Link & Button Analytics** | LinkClick, PhoneClick, EmailClick-এর type, destination host, source page path ও count | কোন host/path-এ click বেশি তা বোঝায়। Link text, phone number, email address, destination query/fragment রাখা হয় না। |
| 13 | **Campaign Attribution** | Browser campaign-এ পাওয়া UTM source এবং normalized source group-এর event count | UTM traffic observation; session stitching, click ID, ad-platform attribution, spend, ROAS বা ROI নয়। Medium/name event-এ রাখা হতে পারে, কিন্তু এই table source group দেখায়। |
| 14 | **E-commerce Intelligence** | placed order, gross order value, pending/cancelled count | Existing orders table-ভিত্তিক। Cancelled বাদ; pending/COD থাকতে পারে। Payment settled revenue, refund/return, net margin বা ad-driven revenue নয়। |
| 15 | **AI Planning & Reports** | ৭/৩০/৯০ দিনের aggregate snapshot দিয়ে Bangla/English report queue ও history | Summary এবং ৭/৩০/৯০ দিনের focus/actions/evidence/confidence/ROI assumptions দেয়। কম data হলে API call হয় না; AI advice-এর ফল নিশ্চিত নয়। |
| 16 | **Recommendations / Action Tracker** | **NOT_YET_IMPLEMENTED** notice | AI recommendation assign, owner/status/due date বা outcome track করার workflow নেই। AI report-এর action list কেবল report text। |
| 17 | **Smart Alerts** | **NOT_YET_IMPLEMENTED** notice | Threshold rule, notification/email delivery বা alert state lifecycle নেই। Data quality menu-র local warning-গুলো alert delivery নয়। |
| 18 | **Deduplication & Data Quality** | Unique event IDs/duplicate copies, queue driver এবং collection/consent/provider gap messages | Internal metrics-এ একই name+ID browser/server copy এক unique event হিসেবে গোনা হয়। Provider-এর deduplication/diagnostic score নিজে যাচাই করে না। |
| 19 | **Delivery Logs & Retry** | Destination/channel/status/attempt/HTTP summary; readiness pass করা blocked/failed server row retry | Worker-এর delivery অবস্থা ও safe retry। Retry করতে queue, consent, collection, route, credential ও matching/client identifiers প্রস্তুত চাই। |
| 20 | **Consent & Privacy** | Consent required, matching enabled এবং retention status; edit link | Storefront consent gate ও matching/retention ব্যাখ্যা করে। পূর্ণ CMP, আইনগত পরামর্শ, ব্যক্তি-ভিত্তিক DSAR export/delete নেই। |
| 21 | **System Health** | Queue driver, collection, provider browser/server toggle, AI toggle | App-এর local config snapshot। Worker চলছে কি না, external token valid কি না, cron age বা provider-side acceptance যাচাই করে না। |
| 22 | **Settings / Configuration Backup** | Provider ID, route ও non-secret setting-সহ JSON download | Secret বাদ যায়। Restore/import feature নেই; downloaded JSON-এ sensitive secret থাকে না, তবে sharing-এর আগে review করুন। |

উপরের সব menu-তে page header-এর date range ৭, ৩০ বা ৯০ দিন। **Export safe CSV** নির্বাচিত range-এর event export করে; menu নাম বদলালেও একই export button।

### প্রতিটি menu-র কাজ ও configure করার নিয়ম

নিচের ধাপগুলো menu-র কাজের সঙ্গে সেটআপও বলে। Menu ২, ৩, ৬, ৭, ৮ ও ২০ একই settings form-এর ভিন্ন প্রবেশপথ—এক জায়গায় Save করলে অন্য menu-তেও সেই configuration দেখা যায়।

#### ১. Overview Dashboard

**কাজ:** নির্বাচিত সময়ের local event rows, unique IDs, duplicate copies, orders, funnel, link/campaign observations এবং queue/data-quality gap এক জায়গায় দেখায়।

**Configure:** এখানে আলাদা settings নেই। উপরের selector থেকে ৭/৩০/৯০ দিন বেছে নিন। তথ্য পেতে Configuration-এ collection, consent এবং প্রযোজ্য routes configure করে বাস্তব storefront action করুন। CSV দরকার হলে **Export safe CSV** চাপুন।

**যেভাবে বুঝবেন:** Observed rows হলো app-এ গৃহীত records; unique ID আলাদা হিসাব। Order value non-cancelled orders-এর gross total—paid revenue বা ad attribution নয়।

#### ২. Configuration / Setup Wizard

**কাজ:** Tracking চালু করার master switches, consent/retention, provider destinations, AI control এবং routing matrix একই Save form-এ রাখে।

**Configure করার ক্রম:** (১) consent requirement এবং retention ঠিক করুন, (২) first-party collection চালু করুন, (৩) Test Mode রেখে provider ID/secret দিন, (৪) provider browser/server toggle প্রয়োজনমতো চালু করুন, (৫) Event Routing Matrix-এ সঠিক event route নির্বাচন করুন, (৬) queue worker প্রস্তুত করুন, (৭) সব দেখে **Save tracking configuration** চাপুন। Collection, provider route ও AI শুরুতে off থাকে।

Secret field ফাঁকা রাখলে আগের secret অক্ষত থাকে; secret মুছতে আলাদা **Clear saved ...** checkbox দিন। Validation error হলে error message দেখে আবশ্যক ID/secret পূরণ করুন। Save form provider-কে test call করে না।

#### ৩. Platform & Pixel Management

**কাজ:** কোন external platform-এ পাঠাবেন তার public identifier ও server credential যোগ করে browser/server channel enable করে।

**Meta:** Pixel ID এবং Graph API version দিন। Browser Pixel চাইলে browser toggle চালু করুন। CAPI চাইলে access token, version এবং Test Mode-এ Test Events code দিন; server toggle-ও চালু করুন। Meta version support বদলায়, তাই বর্তমানে supported version-ই ব্যবহার করুন।

**TikTok:** Pixel ID দিন; Events API server-এর জন্য access token ও server toggle লাগবে। Test Mode-এ browser tag suppress হয় এবং server API blocked থাকে—এটি safety gate।

**GA4:** Web stream-এর Measurement ID দিন। Browser tag-এর জন্য browser toggle; Measurement Protocol-এর জন্য API secret ও server toggle। Server Purchase correlate করতে grant পাওয়া visitor-এ GA client ID cookie পাওয়া থাকতে হবে।

**Google Ads:** Conversion ID ও Conversion label দিন, browser conversion toggle চালু করুন। Browser conversion code কেবল Purchase-এর জন্য tag পাঠায়; তাই Purchase browser route-ই tick করুন। Server upload checkbox disabled, কারণ OAuth/API integration নেই।

**OpenAI:** API key, supported Responses API model, reports/day (০ হলে বন্ধ; সর্বোচ্চ ৫), output token cap (১০০–৩০০০) দিয়ে on-demand generation enable করুন। Report queue চালাতে async worker আবশ্যক। Credential কোথা থেকে পাবেন তার ধাপ [CREDENTIAL_SETUP.md](CREDENTIAL_SETUP.md)-এ আছে।

প্রতিটি credential field পূরণ করলেই event পাঠানো শুরু হয় না; সংশ্লিষ্ট route, collection, consent এবং queue condition-ও ঠিক থাকতে হবে।

#### ৪. Event Management

**কাজ:** সাম্প্রতিক ২৫টি করে local event-এর সময়, নাম, source (browser/server), event ID, consent, page path এবং থাকলে delivery status দেখায়।

**Configure:** এখানে event rule বানানোর setting নেই। Event চাইলে Configuration-এ collection চালু করুন, consent grant দিন এবং storefront-এ সংশ্লিষ্ট বাস্তব action করুন। Event list pagination দিয়ে পুরোনো row দেখুন। নতুন event না থাকলে collection/consent, page environment, event trigger ও browser network যাচাই করুন।

#### ৫. Visual Trigger Builder

**কাজ:** ভবিষ্যতে selector দিয়ে page element trigger define করার জায়গা হিসেবে menu আছে।

**Configure:** বর্তমানে **NOT_YET_IMPLEMENTED**; selector, custom event, versioning বা publish control নেই। Setup করার মতো field নেই। Arbitrary JavaScript/selector তৈরি হয়েছে ধরে live store-এ custom trigger configure করবেন না। এখন কেবল Event catalog-এ লেখা built-in storefront hooks কাজ করে।

#### ৬. Event Routing Matrix

**কাজ:** ৯টি canonical event-কে Meta/TikTok/GA4/Google Ads-এর browser/server channel-এ কোনগুলো পাঠানো যাবে তা event ধরে ঠিক করে।

**Configure:** Matrix-এ event row ও destination/channel column-এর checkbox টিক দিয়ে form-এর **Save tracking configuration** চাপুন। উদাহরণ: storefront browser PageView/ViewContent/AddToCart/InitiateCheckout/Purchase দরকার হলে নির্বাচিত provider-এর browser column-এ ওই row-গুলো চালু করুন। Server Purchase চাইলে Purchase row-এর server column দিন এবং provider server toggle/credential/queue-ও configure করুন। Google Ads-এর জন্য browser Purchase route নির্বাচন করুন।

একই event browser ও server-এ দুদিকেই পাঠাতে চাইলে consented Purchase ID, provider diagnostics এবং Zaraz duplication আগে যাচাই করুন। Matrix-এ server event-এর row থাকলেও backend বর্তমানে server event হিসেবে Purchase-ই তৈরি করে; অন্য server checkbox নিজে event তৈরি করে না। সব route default-এ off।

#### ৭. Browser Tracking

**কাজ:** Storefront browser থেকে first-party local endpoint-এ event এবং নির্বাচিত হলে Meta Pixel, TikTok Pixel, GA4 gtag বা Google Ads Purchase conversion পাঠায়।

**Configure:** Platform & Pixel Management-এ সংশ্লিষ্ট browser enable checkbox ও public ID দিন; Routing Matrix-এ ঐ event-এর browser route টিক দিন। Local dashboard-এ event রাখতে first-party collection-ও enable করুন। Consent required থাকলে storefront visitor-কে allow করতে হবে; consent-gated tracking configured থাকলেই প্রথম consent prompt দেখায়।

Provider browser tags Test Mode-এ পাঠানো হয় না। Browser tag test করতে staging/test property/domain-এ Test Mode বন্ধ করে যাচাই করুন; live domain-এ শুধু test-এর জন্য production tag চালাবেন না। Admin account, bot/crawler এবং অনুমতি না দেওয়া non-production environment বাদ পড়তে পারে। **data-tracking-ignore** চিহ্নিত link বাদ; Cloudflare Zaraz একই destination-এ থাকলে duplicate পরীক্ষা করুন।

#### ৮. Server Tracking

**কাজ:** Backend verified Purchase থেকে delivery row তৈরি করে worker-এর মাধ্যমে Meta CAPI, TikTok Events API 2.0 বা GA4 Measurement Protocol-এ পাঠায়।

**Configure:** Queue **database** connection-এ দিন এবং **tracking,default** queue worker চালান। Provider server toggle, প্রয়োজনীয় server secret/ID এবং Routing Matrix-এর **Purchase server** route চালু করুন। Collection ও consent gate-ও pass করতে হবে। Meta/TikTok match data পেতে consented checkout matching দরকার; GA4-এর জন্য consent পাওয়া GA client ID দরকার।

বর্তমানে AddToCart/InitiateCheckout-এর server delivery তৈরি হয় না; এরা browser event। TikTok server Test Mode-এ blocked, আর Google Ads server upload নেই। Worker না চললে delivery **queued** থাকতে পারে।

#### ৯. Live Event Inspector

**কাজ:** Event Management-এর একই recent local records “Inspector” নামে দেখায়; source, consent, event ID ও delivery relation দেখে debugging-এ সাহায্য করে।

**Configure:** আলাদা configuration নেই। Collection, consent ও route প্রস্তুত করে test action দিন, তারপর page reload/পৃষ্ঠা বদলে record দেখুন। এটি websocket stream নয়; নতুন ঘটনা তাৎক্ষণিক push করে না।

#### ১০. Analytics Dashboard

**কাজ:** তারিখসীমার KPI ও funnel progress bar দেখায়; overview-এর aggregate analytics view।

**Configure:** শুধু first-party local analytics চাইলে provider account লাগবে না; collection ও consent ঠিক করে events আনুন। Browser/server destination দরকার হলে provider route configure করুন। ৭/৩০/৯০ দিন selector দিয়ে সময়সীমা বদলান।

**সীমা:** custom range, product/destination/environment filters, ad spend ও platform attribution নেই। শূন্য মানে শূন্য sale প্রমাণ নয়—collection/consent off থাকলেও local event zero হতে পারে।

#### ১১. Conversion Funnels

**কাজ:** PageView, ViewContent, AddToCart, InitiateCheckout ও Purchase-এর প্রতি ধাপের distinct event ID count দেখায়।

**Configure:** কোনো funnel builder নেই। Product view/cart/checkout/order-এর বাস্তব storefront hook ঘটতে দিন; প্রযোজ্য collection/consent/provider route configure করুন। তারিখ selector দিয়ে ৭/৩০/৯০ দিন দেখুন।

এটি ধাপভিত্তিক মোট count। একই visitor কে একাধিক ধাপে পাওয়া, cohort conversion %, drop-off attribution বা user journey path তৈরি করে না।

#### ১২. Link & Button Analytics

**কাজ:** Storefront anchor click থেকে LinkClick, tel: click থেকে PhoneClick, mailto: click থেকে EmailClick aggregate করে; page path ও destination host দেখায়।

**Configure:** আলাদা selector/ID দিতে হয় না। Consent ও collection চালু রেখে storefront-এর link click করুন; provider-এ পাঠাতে চাইলে ওই event-এর browser route enable করুন। data-tracking-ignore চিহ্নিত link এবং admin/login/account route বাদ পড়ে।

Phone number/email, link text, full destination path/query/fragment সংরক্ষণ হয় না; তাই report contact target-এর তালিকা নয়।

#### ১৩. Campaign Attribution

**কাজ:** Browser event-এর UTM source/medium/campaign পর্যবেক্ষণ করে; dashboard-এ source ও normalized source group-এর distinct-event count দেখায়।

**Configure:** Ad/newsletter URL-এ নিচের মতো UTM যোগ করুন:

    https://shop.example.com/shop?utm_source=facebook&utm_medium=paid_social&utm_campaign=summer_sale

Code শুধু **utm_source**, **utm_medium**, **utm_campaign** পড়ে; value-গুলো length/character/PII check পাস করতে হবে। Campaign name হলো UTM campaign value। Link-এ email/phone বা ব্যক্তিগত তথ্য দেবেন না।

এটি landing-session, ad click ID, first/last-touch attribution, spend বা ROI join করে না। Table-এ মূলত source count; campaign detail পূর্ণ report নয়।

#### ১৪. E-commerce Intelligence

**কাজ:** Existing orders table থেকে placed, pending/cancelled status ও gross order amount দেখায়।

**Configure:** আলাদা tracking field নেই; এই app-এর checkout/order workflow থেকে order তৈরি হতে হবে। Purchase tracking-এর জন্য committed order, confirmation context এবং tracking collection/consent দরকার। Reporting range order-এর created time ধরে।

Cancelled বাদ পড়ে, pending ও COD order অন্তর্ভুক্ত হতে পারে। Payment state আলাদাভাবে reconcile হয় না; refund, return, net revenue বা ad-linked revenue নয়। তাই এই value-কে paid sales/ROAS ধরে campaign সিদ্ধান্ত নেবেন না।

#### ১৫. AI Planning & Reports

**কাজ:** local aggregate metrics দিয়ে Bangla বা English summary ও ৭/৩০/৯০ দিনের action plan তৈরি করে report history-তে রাখে।

**Configure:** Platform settings-এ OpenAI key ও supported model দিন, daily cap এবং token cap ঠিক করুন, AI enable করে Save করুন। Queue driver async database/অন্য async backend হতে হবে; **tracking** queue worker চালু করুন। এরপর এই menu-তে ৭/৩০/৯০ দিনের input range ও ভাষা বেছে **Queue report** চাপুন।

২০টির কম unique event এবং ৩টির কম order—দুটোই হলে OpenAI request হয় না; report **insufficient_data** হয়। AI-তে শুধু aggregate metric যায়, raw event/order/identity নয়। Report queued থাকলে worker এবং failed-job status দেখুন। Report advice, নিশ্চিত ফল নয়।

#### ১৬. Recommendations / Action Tracker

**কাজ:** ভবিষ্যৎ recommendation assignment ও action outcome track করার জন্য menu।

**Configure:** বর্তমানে **NOT_YET_IMPLEMENTED**; owner, task status, deadline, completion বা outcome field নেই। AI report-এর actions আলাদা task হিসেবে assign করার setting নেই। এই menu কোনো task সংরক্ষণ করে না।

#### ১৭. Smart Alerts

**কাজ:** ভবিষ্যৎ threshold alert/notification-এর জন্য menu।

**Configure:** বর্তমানে **NOT_YET_IMPLEMENTED**; threshold, alert recipient, email/push channel বা acknowledge state নেই। Dashboard-এর quality warning কেবল page-এ দেখা local status; notification নয়। এখানে alert চালু করার checkbox নেই।

#### ১৮. Deduplication & Data Quality

**কাজ:** observed rows বনাম unique event IDs, duplicate copy count, queue driver ও common setup gap দেখায়।

**Configure:** Deduplication rule editor নেই। Browser/server Purchase-এ app-এর persisted order event ID reuse হয়। Local count-এ distinct **(event_name, event_id)** ব্যবহার হয়; browser/server rows আলাদা source হয়েও একই pair একবার count হয়। Data quality উন্নত করতে collection, consent, queue worker, credentials ও প্রয়োজনীয় routes configure করুন।

এই menu provider-specific score/validation দেখে না; একই নামের আলাদা ID বা provider-এ duplicate delivery শনাক্তের জন্য provider diagnostics-ও দেখুন।

#### ১৯. Delivery Logs & Retry

**কাজ:** server delivery-এর queue time, event, provider, status, attempt, HTTP status ও sanitized summary দেখায়; eligible blocked/failed row retry করতে দেয়।

**Configure:** Async worker, provider server enable, credential, route, collection, consent এবং identifiers প্রস্তুত করুন। Retry button চাপার আগে block-এর কারণ ঠিক করুন; তারপর **Retry** চাপলে নতুন background attempt queue হয়। Readiness pass না হলে retry reject হবে।

Retry browser event পুনরায় পাঠানোর control নয়। **accepted** মানে provider request accepted criteria পাস করেছে; event validation বা attribution আলাদা করে verify করতে হবে।

#### ২০. Consent & Privacy

**কাজ:** consent gate, matching toggle ও retention window দেখায়; configuration form-এ ফিরে edit করা যায়।

**Configure:** Settings form-এ **Require the storefront consent grant** চালু রাখুন; visitor grant না দিলে optional tracking বন্ধ থাকবে। Policy ও notice অনুযায়ী retention ৩০–৩৬৫ দিন দিন এবং Laravel scheduler চালু রাখুন। Meta/TikTok server matching-এর জন্য consented hashed checkout field আলাদা করে enable করতে হয়।

Consent checkbox বন্ধ করলে module consent grant ছাড়াও collection/provider send করতে পারে; production-এ policy/legal approval ছাড়া বন্ধ করবেন না। UI basic allow/reject, পূর্ণ CMP নয়; reject cookie existing known analytics/ad cookies clear করে এবং refresh করে। Admin pages, bots/crawlers এবং default non-production environment event capture থেকে বাদ।

#### ২১. System Health

**কাজ:** queue driver, collection status, provider toggles এবং AI toggle-এর local configuration snapshot দেখায়।

**Configure:** এখানে health setting নেই। **sync/null** দেখালে environment-এর queue driver বদলান; queue worker এবং scheduler আলাদাভাবে start/configure করুন। Worker-এর বাস্তব কাজ Delivery Logs/AI report status দেখে, scheduler-এর তালিকা **php artisan schedule:list** দিয়ে যাচাই করুন।

এই screen token test, external API health check, live worker heartbeat বা cron last-run timestamp করে না। Local “enabled” দেখানো মানেই provider valid নয়।

#### ২২. Settings / Configuration Backup

**কাজ:** current non-secret tracking setting ও route-সহ JSON download করে; secret বাদ রাখে।

**Configure/use:** **Download public configuration JSON** চাপুন এবং file-টি সীমিত access-এ রাখুন। নতুন environment-এ JSON import/restore নেই—settings UI-তে values আবার বসাতে হবে। API token/secret/key এই download-এ থাকবে না; সেগুলোর নিরাপদ secret-manager/credential record আলাদা রাখতে হবে।

#### সব menu-র সাধারণ date range ও export

Analytics data দেখানো menu-তে header selector থেকে **Last 7 days**, **Last 30 days** বা **Last 90 days** বেছে নিন। **Export safe CSV** ওই selected range-এর event row ডাউনলোড করে; direct export endpoint ১–৯০ দিন গ্রহণ করে। CSV-তে raw search, contact detail, match hash, IP বা User-Agent নেই এবং spreadsheet formula injection-এর জন্য dangerous prefix escape করা হয়।

## ৪. Event catalog: কোন কাজ হলে event হয়

| Canonical event | Trigger | কী বোঝায় / নোট |
|---|---|---|
| **PageView** | Storefront page load এবং History API/popstate path change | SPA-like path transition-ও ধরা হয়। |
| **ViewContent** | Product detail page-এর বিদ্যমান “Product Viewed” context | Product detail দেখা; product metadata সীমিত। |
| **AddToCart** | **CartService::add()** সফল হওয়ার পরে flashed action | Server cart update fail হলে event হয় না। |
| **InitiateCheckout** | Non-empty cart-এ checkout page valid ভাবে render হলে | Stable checkout ID property হতে পারে। |
| **Purchase** | Order DB transaction commit হওয়ার পর server event; authorized confirmation page-এ browser copy | একই persisted order event ID ব্যবহার হয়। এটা order placed; paid/delivered নয়। |
| **LinkClick** | Storefront anchor click | Admin/login/account routes বাদ; শুধু host ও page path। |
| **PhoneClick** | **tel:** anchor click | Phone number payload-এ যায় না। |
| **EmailClick** | **mailto:** anchor click | Email address payload-এ যায় না। |
| **Search** | Completed **/shop?q=...** result context | Raw query বাদ; search length ও result count মাত্র। |

Existing Cloudflare Zaraz event catalog আলাদা: **EVENT_REGISTRY.md**-এ বিস্তৃত Zaraz/custom event এবং কোনগুলো বাস্তবে implemented বা awaiting feature তা আছে। এই module সেই সব Zaraz event-কে স্বয়ংক্রিয়ভাবে provider-এ route করে না; এই গাইডের ৯টি canonical event-ই direct module-এর allowlist।

### Event data ও নিরাপত্তার সীমা

Receiver **/tracking/events** same-origin, CSRF এবং ৬০ request/minute rate limit দিয়ে সুরক্ষিত; ৯টি event name ও allowlisted property ছাড়া কিছু গ্রহণ করে না। Admin AI report request ৩/minute এবং delivery retry ১০/minute throttle-ও পায়। Raw IP, User-Agent, full URL query/fragment, search term, customer email/phone/name/address event property-তে রাখা হয় না। Referrer host, page path, screened UTM values ও সীমিত ecommerce metadata থাকতে পারে।

Consent grant থাকলে GA client/session ID পাওয়া গেলে session/consented state-এ রাখা হয়—এগুলো ব্যক্তির নাম নয়, কিন্তু analytics identifiers; privacy notice-এ বর্ণনা করুন। Optional matching checkbox চালু থাকলে কেবল server Purchase-এর checkout email/phone normalized করে SHA-256 hash, encrypted at rest হিসেবে রাখা হয় এবং supported server provider matching-এ ব্যবহার হয়। Hash-ও pseudonymous personal data হিসেবে বিবেচনা করুন; consent/policy ছাড়া enable করবেন না।

## ৫. Provider route, match quality এবং delivery অর্থ

### Meta Event Match Quality (EMQ)

Meta Events Manager-এর Event Match Quality হলো Meta-র নিজস্ব diagnostics/score; এই admin panel score গণনা বা দেখায় না। Matching data দিতে সাধারণত:

1. Meta Pixel ID, CAPI access token, supported Graph API version দিন; Test Mode server route-এর জন্য Test Events code দিন।
2. Matching করার বৈধ consent/policy থাকলে **Allow consented, SHA-256 hashed checkout email/phone for server matching** চালু করুন।
3. **Enable queued server CAPI** এবং **Purchase → Meta server** route চালু রাখুন।
4. **Enable first-party event collection** ও consent required রাখুন; async queue worker চালু করুন।
5. Test Mode-এ controlled order/consented checkout দিয়ে Meta Events Manager Test Events দেখুন। Event গ্রহণের পরে Meta diagnostics/EMQ-তে matching দেখুন; score update হতে সময় লাগতে পারে।
6. Test complete হলে staging/prod policy অনুযায়ী Test Mode/route পরিবর্তন করুন। একই Purchase browser/server-এ চালালে event name ও ID provider-এ deduplicate হচ্ছে নিশ্চিত করুন।

বর্তমান adapter matching-এ consented checkout email ও phone-এর SHA-256 hash পাঠাতে পারে। Email trim/lowercase করা হয়; phone থেকে punctuation/space সরিয়ে digit রাখা হয়, country code যোগ বা স্থানীয় নম্বরকে international format-এ রূপান্তর করা হয় না। এটি Purchase server event-এ প্রযোজ্য, সব browser event-এ নয়। এই code **_fbp, _fbc, IP, User-Agent** বা **external_id** সংগ্রহ/পাঠায় না; এগুলো যোগ করা বর্তমানে implement করা হয়নি। তাই matching quality উন্নতির সম্ভাবনা আছে, কিন্তু নির্দিষ্ট EMQ score বা attribution guarantee নেই। Meta-র diagnostics-ই চূড়ান্ত; privacy policy, Meta-র বর্তমান normalization specification এবং account-side requirements আলাদাভাবে যাচাই করুন।

### Browser বনাম server

- **Browser**: storefront-এর local JS event পাঠায়; provider Pixel/gtag consent grant, route, provider toggle ও Test Mode gate pass করলে চলে। Browser ID public; token কখনো browser JS-এ দেওয়া হয় না।
- **Server**: backend verified Purchase থেকে local event এবং delivery row তৈরি করে; background worker provider API call করে। Event name, event ID এবং ecommerce properties adapter অনুযায়ী map হয়।
- Purchase browser/server একই order event ID পায়। Local dashboard unique count-এ same name+ID একবার; raw event rows source অনুযায়ী একাধিক হতে পারে।
- বর্তমান backend অন্য server event fabricate করে না। Server route matrix-এ অন্য event tick করলেও server event তৈরি হবে—এমন নয়।

### Delivery status বুঝুন

- **queued**: app-এ job অপেক্ষা করছে; এখনো provider success নয়।
- **processing**: worker attempt করছে।
- **accepted**: adapter provider-এর HTTP/API acceptance criteria পাস করেছে।
- **blocked_queue**: queue driver sync/null; async worker config করুন।
- **blocked_configuration**: destination/server setting/route incomplete বা বন্ধ।
- **blocked_identifier**: প্রয়োজনীয় consented match data বা GA client ID অনুপস্থিত।
- **blocked_test_mode**: বিশেষত TikTok server Test Mode-এ।
- **blocked_consent**: collection/consent gate বন্ধ।
- **failed**: permanent reject বা retry limit শেষ।

Delivery job সর্বোচ্চ ৫ attempt করে; transient network/429/5xx হলে backoff ৬০, ৩০০, ৯০০, ১৮০০ সেকেন্ড। ৫ মিনিটের processing lease duplicate worker claim আটকাতে সাহায্য করে। Retry button শুধু supported server row এবং সব readiness gate pass করলে কাজ করে।

Provider-specific নোট:

- **Meta CAPI**: Test Mode-এ **test_event_code** ব্যবহার; event received মানে ad attribution নয়।
- **TikTok Events API 2.0**: Test Mode-এ server send নিষিদ্ধ; production/off mode-এ server route ও credential লাগবে। Current official schema-তে verified test payload না থাকায় এই guard রাখা হয়েছে।
- **GA4 Measurement Protocol**: consented **ga_client_id** আবশ্যক; API HTTP 2xx হলেও event valid/visible হওয়ার নিশ্চয়তা নয়। GA4 validation/debug tooling দেখুন।
- **Google Ads**: Browser Purchase conversion মাত্র; backend upload/OAuth/conversion action API নেই।

## ৬. Analytics, CSV ও report-এর হিসাব

- **Observed event rows**: local **tracking_events**-এ গৃহীত browser এবং server row সংখ্যা।
- **Unique event IDs**: distinct **(event_name, event_id)**; একই browser/server Purchase copy একবার গোনা হয়।
- **Duplicate copies**: observed row minus unique count। এটি internal reporting; provider deduplication receipt নয়।
- **Funnel**: প্রতিটি ধাপে আলাদা distinct ID; একই person/session-এর cohort funnel বা conversion percentage নয়।
- **Orders placed**: সময়সীমার orders table-এ **cancelled** নয় এমন order count; pending অন্তর্ভুক্ত।
- **Gross order value**: একই non-cancelled order-এর **total** যোগফল, BDT label-এ দেখায়। Payment settlement, refunds/returns, ad spend, net margin বা ROI নয়।
- **Campaign observations**: browser event-এ source UTM উপস্থিত থাকলে distinct event count; source কেবল **meta, google, tiktok, bing, newsletter, direct, other** ইত্যাদিতে normalize হয়। Conversion attribution নয়।
- **Link interactions**: destination host, page path, interaction type; raw URL বা contact target value নয়।
- **Safe CSV**: ১–৯০ দিনের event row; সময়, নাম/ID/source/consent/path/referrer host/UTM/value/currency/link type/host। Hash, email, phone, IP, User-Agent, raw search query বাদ; spreadsheet formula শুরু হতে পারে এমন cell prefix নিরাপদ করা হয়।
- **Configuration backup JSON**: non-secret settings; access token, API secret, OpenAI key বাদ; import/restore নেই।

## ৭. AI Planning & Reports চালানো

1. Configuration-এ API key, Responses API/Structured Outputs supported model, দৈনিক limit এবং output-token cap দিন।
2. **Enable on-demand AI report generation** চালু করে Save করুন।
3. **AI Planning & Reports**-এ ৭/৩০/৯০ দিনের input period এবং Bangla/English বেছে **Queue report** চাপুন।
4. Async queue worker report job process করার পর একই page-এর history দেখুন।

AI-কে কেবল aggregate event total, unique funnel count, order/pending/cancelled count, gross order value ও normalized campaign source group পাঠানো হয়। Raw property, URL, search term, order detail, identity, email, phone, IP বা User-Agent পাঠানো হয় না। Request **store:false**, strict JSON schema এবং output cap ব্যবহার করে। Input period যাই হোক, output-এ ৭/৩০/৯০ দিনের action plan থাকে। ২০টির কম unique event এবং ৩টির কম order হলে insufficient-data report স্থানীয়ভাবে হয়—OpenAI API call হয় না। Daily report limit ০ হলে report বন্ধ। API refusal/error হলে generic failure status থাকে; worker আবশ্যক।

AI-র focus/actions/evidence/confidence/ROI assumptions হলো planning suggestion; এগুলো বাস্তব outcome বা নিশ্চিত revenue forecast নয়। Daily limit/token cap খরচ সীমিত করতে সাহায্য করে, কিন্তু provider account budget-এর বিকল্প নয়।

## ৮. Menu-তে কিছু না দেখালে: দ্রুত যাচাই

1. **503 Service Unavailable**: tracking migration চালানো হয়েছে কি না দেখুন; deploy-এর পর **php artisan migrate --force**।
2. **সব সংখ্যা 0**: collection enabled, consent grant, production/staging route ও event trigger—চারটিই পরীক্ষা করুন। Consent required হলে grant ছাড়া event জমে না।
3. **First-party collection disabled**: Setup-এ collection checkbox চালু করে Save করুন।
4. **Queue: sync/null বা blocked_queue**: .env-এ **QUEUE_CONNECTION=database**, **php artisan config:clear**, **tracking,default** worker চালু; dashboard-এর driver runtime config দেখায়।
5. **Server event নেই**: বর্তমানে server event কেবল committed Purchase; AddToCart/checkout event browser থেকে আসে। GA4-এর জন্য consented client ID; Meta/TikTok-এর জন্য matching data-ও দরকার।
6. **Meta Test Mode validation fail**: Pixel ID/token/Graph version/Test Events code এবং Meta test diagnostics দেখুন; Test Mode-এ browser Pixel বন্ধ থাকাই প্রত্যাশিত।
7. **TikTok server blocked_test_mode**: বর্তমান implementation-এ প্রত্যাশিত guard; এই status retry করে সরানো যাবে না। TikTok test payload support নিশ্চিত না হওয়া পর্যন্ত test mode-এ server send হয় না।
8. **GA4 accepted কিন্তু report-এ নেই**: HTTP acceptance validation নয়; Measurement Protocol diagnostics/debug view এবং event/client/session fields যাচাই করুন।
9. **দুই গুণ event দেখা যায়**: Cloudflare Zaraz এবং direct browser tags একই destination-এ চলছে কি না দেখুন; test route/ID-ও পরীক্ষা করুন।
10. **AI report queued-ই থাকে**: worker **tracking** queue শুনছে কি না, OpenAI key/model setting এবং failed jobs পরীক্ষা করুন।
11. **Old rows নিজে মুছছে না**: scheduler/cron সক্রিয় কি না দেখুন; **php artisan schedule:list** দিয়ে task যাচাই করুন। **php artisan tracking:prune** সরাসরি চালালে expired rows মুছে যায়।
12. **CSS/style না আসে**: admin CSS **public/css/tracking-admin.css**, consent CSS **public/css/tracking-consent.css**; CSP/static file rule, file deployment ও browser network tab পরীক্ষা করুন।

## ৯. বর্তমান অসম্পূর্ণ বা অনুপস্থিত feature

এই তালিকা ইচ্ছাকৃতভাবে স্পষ্ট, যাতে UI label-কে চালু feature ভেবে production setup না হয়:

- Visual selector/trigger rules, selector versioning ও publish/rollback নেই।
- AI recommendation-এর owner assignment, action status/due date/outcome tracker নেই।
- Threshold-based Smart Alerts বা email/push notification নেই।
- Google Ads server-side OAuth/conversion upload নেই।
- Meta EMQ score এই panel-এ নেই; score Meta Events Manager-এ।
- Provider-level event validation/attribution/ROI এই app হিসাব করে না।
- Analytics-এ visitor cohort/session funnel, ad click IDs, spend/cost, cross-device identity stitching বা custom range নেই।
- Product wishlist, refunds, returns, payment success/failure callbacks ইত্যাদি app workflow না থাকলে fabricated event তৈরি হয় না। Broader existing Zaraz registry-র awaiting status **EVENT_REGISTRY.md**-এ দেখুন।
- Consent UI একটি basic allow/reject control, full CMP/legal compliance নয়।
- Per-customer event search/export/delete বা DSAR workflow নেই; raw direct customer ID event table-এ design করা হয়নি।
- Configuration backup থেকে restore/import নেই।
- Health screen external provider credential verify, worker heartbeat বা cron heartbeat করে না।

## ১০. বিস্তারিত reference

- [Tracking overview](README.md)
- [Provider credential setup](CREDENTIAL_SETUP.md)
- [Event schema ও platform mapping](EVENT_SCHEMA.md)
- [Canonical/legacy event registry](EVENT_REGISTRY.md)
- [Storefront instrumentation map](INSTRUMENTATION_MAP.md)
- [Meta Event Match Quality notes](META_MATCH_QUALITY.md)
- [Deduplication ও idempotency](DEDUPLICATION.md)
- [Privacy ও security details](PRIVACY_SECURITY.md)
- [AI report input/output](AI_REPORTS.md)
- [Shared hosting queue/scheduler setup](SHARED_HOSTING_DEPLOY.md)
- [Implementation status](FINAL_IMPLEMENTATION_STATUS.md)
- [Migration rollback notes](ROLLBACK.md)

