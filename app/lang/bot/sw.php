<?php

// Swahili — customer-facing WhatsApp bot strings. Placeholders: {name} etc.
// Keep keys in sync with en.php.

return [
    // Language names (shown in the settings chooser, always in their own tongue).
    'lang_name_sw' => 'Kiswahili',
    'lang_name_en' => 'English',

    // Reusable snippets.
    'back_to_menu' => 'Tuma "#" kurudi menu kuu.',
    'restart' => 'Tuma "#" kuanza upya.',
    'default_service_name' => 'Huduma',
    'default_customer_name' => 'Mteja',
    'press_button_reminder' => "⚠️ Tafadhali bonyeza moja ya vitufe hapo juu 👆\n\n_Tuma \"#\" kurudi mwanzo_",
    'not_understood_menu' => "🤔 Samahani, sijaelewa.\n\nTafadhali bonyeza *Fungua Menyu* hapo juu kuchagua, au tuma *#* kuona menu kuu.",
    'generic_error' => 'Hitilafu imetokea. Tuma "#" kuanza upya.',
    'route_error' => "Samahani, hitilafu imetokea. Tuma '#' kuanza upya.",

    // Buttons / list controls.
    'btn_contact_admin' => 'Wasiliana Na Admin',
    'btn_other_number' => '✏️ Namba Nyingine',
    'btn_cancel' => '❌ Sitisha',
    'btn_yes_continue' => 'Ndio, Endelea 🚀',
    'btn_no_cancel' => 'Hapana, Sitisha ❌',
    'btn_yes_pay' => 'Ndio, Lipa Sasa 💳',
    'btn_no_cancel_plain' => 'Hapana, Sitisha',
    'btn_open_menu' => 'Fungua Menyu',
    'btn_platforms' => 'Mitandao',
    'btn_choose_service' => 'Chagua Huduma',
    'btn_packages' => 'Vifurushi',

    // Main menu.
    'menu_header' => 'Main Menu',
    'menu_welcome' => "👑 *KARIBU, {name_upper}!*\n\n" .
        "Hujambo {name}! 👋 Karibu.\n\n" .
        "Mimi ni mshirika wako nipo hapa kukusaidia kukuza akaunti zako za mitandao ya kijamii.\n\n" .
        "Tunatoa huduma za haraka, salama, na nafuu kuongeza followers, likes, na views 🚀📈\n\n" .
        "👇 Chagua chaguo hapa chini:",
    'menu_new_order_title' => '🛒 Weka Oda Mpya',
    'menu_new_order_desc' => 'Followers, likes au views',
    'menu_topup_title' => '💰 Weka Pesa',
    'menu_topup_desc' => 'Mobile Money / Card',
    'menu_profile_title' => '👤 Wasifu Wangu',
    'menu_profile_desc' => 'Salio na matumizi yako',
    'menu_referral_title' => '🎁 Mwalike Rafiki',
    'menu_referral_desc' => 'Pata bonus kwa kila rafiki',
    'menu_track_title' => '📦 Fuatilia Oda',
    'menu_track_desc' => 'Angalia hatua ya oda yako',
    'menu_support_title' => '🎧 Huduma Kwa Wateja',
    'menu_support_desc' => 'Wasiliana na admin',
    'menu_settings_title' => '⚙️ Mipangilio',
    'menu_settings_desc' => 'Language na sarafu',
    'menu_group_title' => '👥 KuzaPanel Group',
    'menu_group_desc' => 'Jiunge na taarifa zetu',
    'menu_website_title' => '🌐 KuzaPanel Website',
    'menu_website_desc' => 'Huduma zaidi mtandaoni',

    // Maintenance mode.
    'maintenance_message' => "🛠️ *TUNAFANYIWA MATENGENEZO*\n\n" .
        "Samahani, huduma zetu kwa sasa zinafanyiwa matengenezo kidogo. Tafadhali jaribu tena baadaye.\n\n" .
        "Asante kwa uvumilivu wako! 🙏",

    // Session expiry.
    'session_expired' => "⏰ Muda wa mazungumzo yako umepita kwa kukaa kimya.\n\nHakuna wasiwasi — tunaanza upya! 👇",
    'awaiting_topup_confirmation' => "⏳ *TUNASUBIRI MALIPO YAKO*\n\n" .
        "Bado hatujapokea uthibitisho wa malipo yako.\n\n" .
        "📲 *Kama hujapokea USSD:* angalia simu yako vizuri, au bonyeza *Nitumie USSD tena* hapa chini.\n" .
        "✅ *Kama umeshalipa:* subiri sekunde chache — tutakutumia ujumbe malipo yatakapothibitishwa.\n\n" .
        "_Tuma \"#\" kurudi menu kuu wakati wowote._",
    'topup_choose_action' => "⏳ *TUNASUBIRI MALIPO YAKO*\n\n" .
        "Bado hatujapokea uthibitisho wa malipo yako. Ungependa kufanya nini?\n\n" .
        "📲 *Nitumie USSD tena* — kupokea ombi la malipo upya\n" .
        "❌ *Sitisha malipo* — kufuta na kurudi menu kuu\n\n" .
        "_Au tuma \"#\" kurudi menu kuu wakati wowote._",
    'btn_resend_ussd' => '📲 Nitumie USSD tena',
    'btn_cancel_payment' => '❌ Sitisha malipo',
    'payment_cancelled' => "❌ *MALIPO YAMESITISHWA*\n\n" .
        "Hakuna pesa iliyokatwa. Tuma \"#\" kuanza upya wakati wowote.",
    'topup_session_expired' => "⏰ *MUDA WA MALIPO UMEPITA*\n\n" .
        "Hatukupokea uthibitisho wa malipo yako kwa muda. Hakuna pesa iliyokatwa.\n\n" .
        "Kama bado unataka kuweka pesa au kuagiza, tunaanza upya 👇",
    'order_payment_expired' => "⏰ *ODA #{number} IMEGHAIRIWA*\n\n" .
        "Oda uliyokuwa ukiitengeneza imeghairiwa kwa sababu hukukamilisha malipo kwa wakati.\n\n" .
        "Hakuna pesa iliyokatwa. Tuma \"#\" kuanzisha oda mpya wakati wowote.",

    // Settings menu.
    'settings_menu' => "⚙️ *MIPANGILIO*\n\nUnataka kubadilisha nini?",
    'settings_option_language' => '🌐 Lugha',
    'settings_option_currency' => '💱 Currency',
    'settings_press_option' => "⚠️ Tafadhali bonyeza chaguo moja hapo juu 👆\n\n_Tuma \"#\" kurudi menu kuu_",

    // Language settings.
    'settings_choose_language' => "🌐 *CHAGUA LUGHA*\n\nUnataka kutumia lugha gani?",
    'language_changed' => "✅ Umebadilisha lugha kuwa *Kiswahili*.\n\nTunaendelea kwa Kiswahili. 👇",
    'settings_press_language' => "⚠️ Tafadhali bonyeza lugha moja hapo juu 👆\n\n_Tuma \"#\" kurudi menu kuu_",

    // Currency settings.
    'settings_choose_currency' => "💱 *CHAGUA CURRENCY*\n\nUnataka bei zionekane kwa currency gani?",
    'currency_name_tzs' => 'TZS (Tanzania)',
    'currency_name_kes' => 'KES (Kenya)',
    'currency_name_ugx' => 'UGX (Uganda)',
    'settings_press_currency' => "⚠️ Tafadhali bonyeza currency moja hapo juu 👆\n\n_Tuma \"#\" kurudi menu kuu_",

    // Wallet top-up gateway choice.
    'gateway_choice_menu' => "💰 *WEKA PESA*\n\nChagua njia unayotaka kutumia kuweka pesa kwenye salio lako:",
    'gateway_choice_section' => 'Njia za Malipo',
    'btn_choose' => 'Chagua',
    'gateway_name_snippe' => 'Weka Pesa',
    'gateway_name_zenopay' => 'ZenoPay',
    'gateway_name_harakapay' => 'HarakaPay',
    'gateway_desc_mobile_money' => 'Mobile Money / Kadi',
    'currency_changed' => "✅ Umebadilisha currency yako.\n\nBei zote zitaonekana kwa currency mpya kuanzia sasa. 👇",

    // AI / support.
    'ai_unavailable' => 'Samahani, huduma ya AI haipatikani kwa sasa. Bonyeza kitufe hapa chini kuongea na admin wetu moja kwa moja.',
    'ai_ask_text' => 'Tafadhali andika swali lako kwa maandishi.',
    'ai_connect_admin' => 'Sawa, bonyeza kitufe hapa chini kuongea na admin wetu moja kwa moja.',
    'ai_error' => "⚠️ Samahani, sijaweza kupata jibu kwa sasa.\n\nTuma neno *admin* kuongea na binadamu, au \"#\" kurudi menu kuu.",
    'support_ai_intro' => "🎧 *Huduma Kwa Wateja*\n\n" .
        "Habari! Mimi ni msaidizi wa AI wa KuzaPanel. Niulize swali lolote kuhusu huduma zetu, malipo, au jinsi ya kuagiza.\n\n" .
        "💡 Ukihitaji kuongea na admin moja kwa moja, tuma neno *admin*.\n" .
        "_Tuma \"#\" wakati wowote kurudi menu kuu._",
    'support_cta' => "🎧 *Huduma Kwa Wateja*\n\nBonyeza kitufe hapa chini kuongea na admin wetu moja kwa moja.",

    // Group / website.
    'group_info' => "👥 *KuzaPanel Group*\n\nJiunge na group letu kupata taarifa za huduma zetu na maelekezo:\n{url}",
    'website_info' => "🌐 *KuzaPanel Website*\n\nKupata huduma nyingi zaidi na kwa haraka na salama, tembelea tovuti yetu:\n{url}",

    // Profile.
    'profile' => "👤 *WASIFU WANGU*\n\n" .
        "Jina: {name}\n" .
        "💰 Salio: {balance} {currency}\n" .
        "📊 Jumla Umetumia: {spent} {currency}\n\n" .
        "Tuma \"#\" kurudi kwenye menu kuu.",

    // Referral.
    'referral_invite_text' => 'Habari! Nataka kukuza akaunti yangu ya mitandao. Tumia code yangu: REF {code}',
    'referral_info' => "🎁 *MWALIKE RAFIKI, PATA BONUS*\n\n" .
        "Mwalike rafiki yako atumie KuzaPanel. Rafiki akiweka pesa mara ya kwanza, " .
        "wewe utapata *{percent}%* ya kiasi alichoweka moja kwa moja kwenye salio lako! 💰\n\n" .
        "🔑 Code yako: *{code}*\n" .
        "👥 Marafiki uliowaalika: {count}\n" .
        "💵 Umechuma jumla: {earnings} {currency}\n\n" .
        "📲 Shiriki link hii na marafiki:\n{link}\n\n" .
        "Tuma \"#\" kurudi menu kuu.",
    'referral_bonus' => "🎁 *BONUS YA REFERRAL!*\n\n" .
        "Rafiki uliyemwalika amekamilisha malipo yake ya kwanza! 🎉\n\n" .
        "💰 Umepata bonus ya *{bonus} {currency}* kwenye salio lako.\n\n" .
        "Endelea kualika marafiki zaidi upate bonus zaidi! 🚀\n\n" .
        "© KuzaPanel",

    // Order tracking.
    'track_no_orders' => '📦 Hauna oda yoyote bado. Tuma "#" kuanza oda mpya.',
    'track_header' => "📦 *FUATILIA ODA*\n",
    'track_line' => "🆔 Oda Namba #{number} — {service}\n" .
        "   Kiasi: {qty} | Gharama: {amount} {currency}\n" .
        "   Status: {status}",
    'track_footer' => "\nTuma \"#\" kurudi kwenye menu kuu.",

    // Top-up.
    'topup_prompt' => "💰 *WEKA PESA*\n\n" .
        "Tafadhali andika kiasi unachotaka kuweka kwenye salio lako ({currency}).\n\n" .
        "📌 Mfano: 5000\n\n" .
        "Tuma \"#\" kurudi menu kuu.",
    'topup_invalid' => "⚠️ Kiasi sio sahihi.\n\nTafadhali andika kiasi unachotaka kuweka kwa namba.\n📌 Mfano: 5000",
    'payment_sent_menu' => "{intro}\n\n👇 Chagua huduma kuanza:",

    // Payment method / phone.
    'payment_method' => "💳 *NJIA YA MALIPO*\n" .
        "💰 Unahitaji: {amount} {currency}\n\n" .
        "Utalipa {amount} {currency}.\n\n" .
        "Unataka kulipia kwa namba gani?",
    'cancelled' => 'Imesitishwa. Tuma "#" kuanza upya.',
    'payment_phone_prompt' => "📱 *NAMBA YA MALIPO*\n\n" .
        "Tafadhali andika namba ya simu utakayolipia (Mobile Money).\n\n" .
        "📌 Mfano: 0712345678\n\n" .
        "Tuma \"#\" kurudi menu kuu.",
    'payment_phone_invalid' => "⚠️ Namba sio sahihi.\n\nTafadhali andika namba kamili ya simu (Mobile Money).\n📌 Mfano: 0712345678",
    'min_amount' => "⚠️ Kiwango cha chini cha malipo kwa namba hii ni {min} {currency}.\n\n" .
        "Tafadhali jaribu tena na kiasi cha juu zaidi, au tumia namba ya mtandao mwingine.\n\n" .
        "Tuma \"#\" kuanza upya.",
    'payment_failed' => "Samahani, malipo hayakuanzishwa: {message}\nTuma \"#\" kuanza upya.",
    'topup_sent' => "🪙 Tumetuma ombi la malipo!\n\n" .
        "📲 Angalia simu yako (USSD push) na ingiza PIN kuthibitisha *{charge} {currency}*.\n\n" .
        "🆔 Kumbukumbu: {reference}\n\n" .
        "💰 Salio lako litaongezwa kwa {amount} {currency} moja kwa moja malipo yatakapothibitishwa.",
    'order_payment_sent' => "✅ *OMBI LIMETUMWA*\n\n" .
        "Kiasi cha kulipa: *{charge} {currency}*\n" .
        "Namba: {phone}\n\n" .
        "📲 Angalia simu yako — utapokea ujumbe wa kuthibitisha malipo (USSD). Weka PIN yako kukamilisha.\n\n" .
        "🚀 Oda yako itakamilika moja kwa moja malipo yatakapothibitishwa.\n\n" .
        "📲 *Hujapokea USSD?* Bonyeza *Nitumie USSD tena* hapa chini.\n" .
        "_Au tuma \"#\" kurudi menu kuu._",

    // Snippe Sessions checkout (Kenya/Uganda) — link instead of USSD push.
    'topup_sent_checkout' => "🪙 Tumetuma ombi la malipo!\n\n" .
        "📲 Bonyeza kitufe hapa chini kukamilisha malipo ya *{charge} {currency}*.\n\n" .
        "💰 Salio lako litaongezwa moja kwa moja malipo yatakapothibitishwa.",
    'order_payment_sent_checkout' => "✅ *OMBI LIMETUMWA*\n\n" .
        "Kiasi cha kulipa: *{charge} {currency}*\n\n" .
        "📲 Bonyeza kitufe hapa chini kukamilisha malipo.\n\n" .
        "🚀 Oda yako itakamilika moja kwa moja malipo yatakapothibitishwa.",
    'btn_complete_payment' => '💳 Lipa Sasa',

    // Platform / service selection.
    'no_services' => 'Samahani, hakuna huduma zinazopatikana kwa sasa.',
    'platform_menu' => "📱 *CHAGUA MTANDAO*\n" .
        "Sawa {name}, tukuze akaunti yako! 🚀\n\n" .
        "Unataka kukuza mtandao gani leo?",
    'choose_platform' => 'Tafadhali chagua platform kutoka kwenye orodha.',
    'platform_generic_desc' => 'Huduma mbalimbali',
    'no_services_platform' => 'Samahani, hakuna huduma za platform hii kwa sasa. Tuma "#" kuanza upya.',
    'choose_category' => "📂 *CHAGUA AINA*\n" .
        "Umechagua {platform}. Unahitaji aina gani?",
    'btn_categories' => 'Aina',
    'categories_header' => 'Aina',
    'category_other' => 'Nyingine',
    'choose_category_reminder' => "⚠️ Tafadhali chagua aina kutoka kwenye orodha hapo juu 👆\n\n_Tuma \"#\" kurudi mwanzo_",
    'service_menu' => "🎯 *CHAGUA HUDUMA ZA {platform_upper}*\n" .
        "Chaguo nzuri {name}! Umechagua {platform}.\n\n" .
        "Unahitaji nini haswa kwa akaunti yako? 👇",
    'price_per_1000' => '{price} {currency} kwa 1000',
    'choose_service_reminder' => "⚠️ Tafadhali chagua huduma kutoka kwenye orodha hapo juu 👆\n\n_Tuma \"#\" kurudi mwanzo_",
    'service_unavailable' => 'Huduma hii haipatikani tena. Tuma "#" kuanza upya.',

    // Quantity.
    'qty_menu' => "🔢 *CHAGUA KIASI*\n" .
        "Unataka kuongeza {service} ngapi?\n\n" .
        "Chagua kifurushi 👇",
    'qty_total' => 'Jumla: {total} {currency}',
    'qty_custom_title' => '✏️ Kiasi Chako',
    'qty_custom_desc' => 'Min: {min} | Max: {max}',
    'qty_custom_prompt' => 'Tafadhali andika idadi (kati ya {min} na {max}) unayotaka:',
    'qty_package_reminder' => "⚠️ Tafadhali chagua kifurushi kutoka kwenye orodha hapo juu 👆\n\n_Tuma \"#\" kurudi mwanzo_",
    'qty_invalid' => 'Idadi sio sahihi. Tafadhali andika namba kati ya {min} na {max}:',

    // Link request.
    'link_request' => "🔗 *TUMA LINK YAKO*\n\n" .
        "Habari {name}, unaagiza {qty}.\n\n" .
        "{image_note}📱 Hatua:\n{steps}\n\n" .
        "📌 Mfano: {example}\n\n" .
        "Tuma \"#\" kurudi menu kuu.",
    'link_see_image' => "👉 Angalia picha hapo juu kuona format sahihi.\n\n",
    'link_invalid' => "⚠️ Link uliyotuma sio sahihi.\n\n" .
        "Tafadhali tuma link kamili inayoanza na http:// au https://\n" .
        "📌 Mfano: https://instagram.com/jinaako\n\n" .
        "_Tuma \"#\" kurudi mwanzo_",

    // Link instruction steps (per platform + type). Examples/images stay in code.
    'link_steps_generic' => "1️⃣ Fungua akaunti/post yako ya {platform}\n2️⃣ Bonyeza ikoni ya share\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
    'link_steps_instagram_profile' => "1️⃣ Fungua profile yako ya Instagram\n2️⃣ Bonyeza Share profile\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
    'link_steps_instagram_post' => "1️⃣ Fungua post yako ya Instagram\n2️⃣ Bonyeza ikoni ya share (✈️)\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
    'link_steps_tiktok_profile' => "1️⃣ Fungua account yako ya TikTok\n2️⃣ Bonyeza ikoni ya share juu\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
    'link_steps_tiktok_post' => "1️⃣ Fungua post yako ya TikTok\n2️⃣ Bonyeza ikoni ya share (✈️)\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
    'link_steps_facebook_profile' => "1️⃣ Fungua Account yako ya Facebook\n2️⃣ Bonyeza vidoti vitatu\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
    'link_steps_facebook_post' => "1️⃣ Fungua post yako ya Facebook\n2️⃣ Bonyeza ikoni ya share\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
    'link_steps_youtube_profile' => "1️⃣ Fungua profile yako ya YouTube\n2️⃣ Bonyeza Share profile\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
    'link_steps_youtube_post' => "1️⃣ Fungua post yako ya YouTube\n2️⃣ Bonyeza ikoni ya share (✈️)\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",

    // Order confirmation / completion.
    'order_confirm' => "✅ *UTHIBITISHO WA MWISHO*\n" .
        "Tafadhali hakiki maelezo ya oda yako:\n\n" .
        "📡 Mtandao: {platform}\n" .
        "🎯 Huduma: {service}\n" .
        "🔗 Link: {link}\n" .
        "🔢 Kiasi: {qty}\n\n" .
        "💰 Gharama: {amount} {currency}\n\n" .
        "Je, tuendelee na oda hii?",
    'order_cancelled' => 'Oda imesitishwa. Tuma "#" kuanza upya.',
    'wallet_debit_error' => 'Samahani, hitilafu imetokea wakati wa kutumia salio. Tuma "#" kuanza upya.',
    'low_balance' => "⚠️ *SALIO DOGO*\n" .
        "Salio Halitoshi\n\n" .
        "Unahitaji ziada ya: {shortfall} {currency}\n\n" .
        "Je, unataka kulipa sasa ili kukamilisha oda?",
    'order_provider_line' => "🆔 Oda Namba: #{provider}\n",
    'order_received' => "🎉 *Oda Imepokelewa Kikamilifu!*\n\n" .
        "{provider_line}⏳ Tumeanza kuifanyia kazi mara moja! 🚀\n\n" .
        "Unaweza kuifuatilia kwa kutumia kitufe cha 'Fuatilia Oda' kwenye menu kuu.\n\n" .
        "_Tuma # kurudi menu kuu_",

    // Deposit confirmation.
    'deposit_confirmed' => "✅ Malipo ya {amount} {currency} yamethibitishwa na yameongezwa kwenye salio lako!",

    // Manual balance adjustment by admin.
    'balance_credited_by_admin' => "💰 *SALIO LIMEONGEZWA*\n\n" .
        "Admin ameongeza *{amount} {currency}* kwenye salio lako.\n" .
        "{note_line}\n" .
        "Salio lako jipya: *{balance} {currency}*",
    'balance_debited_by_admin' => "⚠️ *SALIO LIMEPUNGUZWA*\n\n" .
        "Admin amepunguza *{amount} {currency}* kwenye salio lako.\n" .
        "{note_line}\n" .
        "Salio lako jipya: *{balance} {currency}*",
    'balance_adjustment_note_line' => "📝 Sababu: {note}\n",

    // Cron order status notifications.
    'order_completed_notify' => "🎉 *ODA IMEKAMILIKA!*\n\n" .
        "🆔 Oda Namba: #{number}\n" .
        "🎯 Huduma: {service}\n" .
        "🔢 Kiasi: {qty} {unit}\n" .
        "🔗 Link: {link}\n\n" .
        "✅ Huduma yako imewasilishwa kikamilifu. Asante kwa kutuamini! 🙏\n\n" .
        "Tuma \"#\" kuanza oda nyingine.\n\n" .
        "© KuzaPanel",
    'order_cancelled_notify' => "⚠️ *ODA IMEGHAIRIWA*\n\n" .
        "🆔 Oda Namba: #{number}\n" .
        "🎯 Huduma: {service}\n\n" .
        "Samahani, oda hii haikuweza kukamilika. Tafadhali wasiliana na huduma kwa wateja kwa msaada.\n\n" .
        "Tuma \"#\" kurudi menu kuu.\n\n" .
        "© KuzaPanel",
];
