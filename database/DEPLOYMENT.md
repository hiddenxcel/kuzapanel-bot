# KuzaPanel Bot — Mwongozo wa Kupandisha (cPanel + bot.kuzapanel.com)

## 1. Tengeneza Subdomain
cPanel > **Domains** (au "Subdomains"):
- Subdomain: `bot`
- Domain: `kuzapanel.com`
- **Document Root:** `bot.kuzapanel.com/public`  ⬅️ MUHIMU: lazima iishie na `/public`

## 2. Pakia Faili
Njia rahisi: File Manager > nenda kwenye folda ya `bot.kuzapanel.com` (au home),
pakia ZIP ya bot nzima, kisha "Extract". Hakikisha muundo unakuwa:
```
bot.kuzapanel.com/
├── app/
├── config/
├── cron/
├── database/
├── public/      ← document root inaelekeza HAPA
├── storage/
└── .htaccess
```
USIPAKIE: `tools/` (ngrok), `.git/`, `config/.env` ya local.

## 3. Database
cPanel > **MySQL Databases**:
- Tengeneza database mpya (mfano `kuzXXXX_bot`)
- Tengeneza user mpya + password, mpe ALL PRIVILEGES kwenye hiyo DB
- cPanel > **phpMyAdmin** > chagua DB > **Import** > pakia `database/schema.sql`

## 4. config/.env (production)
Tengeneza `config/.env` (kupitia File Manager > Edit) kwa kutumia `.env.example` kama kigezo:
```
DB_HOST=localhost
DB_NAME=kuzXXXX_bot          # jina kamili la DB ya cPanel
DB_USER=kuzXXXX_botuser      # user wa cPanel
DB_PASS=********

WHATSAPP_TOKEN=...           # bora: permanent token (ona #8)
WHATSAPP_PHONE_NUMBER_ID=1149753201562304
WHATSAPP_VERIFY_TOKEN=kuzapanel_verify_2026
WHATSAPP_BUSINESS_ACCOUNT_ID=1344192421011610
WHATSAPP_DISPLAY_NUMBER=255757492503

ADMIN_PHONE_NUMBER=+255757492503
KUZAPANEL_GROUP_URL=...
KUZAPANEL_WEBSITE_URL=...
REFERRAL_PERCENT=10

ZENOPAY_API_KEY=...
SNIPPE_API_KEY=...
SNIPPE_WEBHOOK_SECRET=...
HARAKAPAY_API_KEY=...

APP_ENV=production
APP_URL=https://bot.kuzapanel.com    # NO /public (document root already = public)
```
⚠️ Kumbuka: kwa production, `APP_URL` HAINA `/public` kwa sababu document root tayari ni public/.
Hii inaathiri webhook URLs na image URLs — zitakuwa `https://bot.kuzapanel.com/webhooks/snippe.php` na `https://bot.kuzapanel.com/assets/instructions/x.jpg`.

## 5. Admin account
cPanel > **Terminal** (au SSH):
```
cd ~/bot.kuzapanel.com
php database/seed_admin.php admin <password-imara>
```
(Kama hakuna Terminal: tumia "Cron Jobs" kuendesha mara moja, au niambie nitengeneze seeder ya web ya muda.)

## 6. Cron Jobs
cPanel > **Cron Jobs**. Ongeza hizi tatu (badilisha path ya `php` na home kama inavyohitajika):
```
*/5 * * * *   /usr/local/bin/php ~/bot.kuzapanel.com/cron/check_orders.php
*/2 * * * *   /usr/local/bin/php ~/bot.kuzapanel.com/cron/check_harakapay.php
0 0 * * *     /usr/local/bin/php ~/bot.kuzapanel.com/cron/daily_summary.php
```

## 7. Meta Webhook
Meta dashboard > WhatsApp > Configuration > Webhook:
- Callback URL: `https://bot.kuzapanel.com/index.php`
- Verify token: `kuzapanel_verify_2026`
- Subscribe: `messages`

## 8. Permanent WhatsApp Token (production lazima)
Meta Business Settings > System Users > tengeneza System User >
mpe WhatsApp permissions > Generate token (chagua "Never expire") >
weka kwenye `WHATSAPP_TOKEN`. Hii inaondoa tatizo la token kuisha kila saa.

## 9. Pima
Tuma "#" kwa namba ya bot. Pitia order + malipo + maelekezo + picha.
