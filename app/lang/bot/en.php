<?php

// English — customer-facing WhatsApp bot strings. Placeholders: {name} etc.
// Keep keys in sync with sw.php.

return [
    // Language names (shown in the settings chooser, always in their own tongue).
    'lang_name_sw' => 'Kiswahili',
    'lang_name_en' => 'English',

    // Reusable snippets.
    'back_to_menu' => 'Send "#" to return to the main menu.',
    'restart' => 'Send "#" to start over.',
    'default_service_name' => 'Service',
    'default_customer_name' => 'Customer',
    'press_button_reminder' => "⚠️ Please tap one of the buttons above 👆\n\n_Send \"#\" to go back_",
    'not_understood_menu' => "🤔 Sorry, I didn't understand that.\n\nPlease tap *Open Menu* above to choose, or send *#* to see the main menu.",
    'generic_error' => 'Something went wrong. Send "#" to start over.',
    'route_error' => "Sorry, something went wrong. Send '#' to start over.",

    // Buttons / list controls.
    'btn_contact_admin' => 'Contact Admin',
    'btn_other_number' => '✏️ Another Number',
    'btn_cancel' => '❌ Cancel',
    'btn_yes_continue' => 'Yes, Continue 🚀',
    'btn_no_cancel' => 'No, Cancel ❌',
    'btn_yes_pay' => 'Yes, Pay Now 💳',
    'btn_no_cancel_plain' => 'No, Cancel',
    'btn_open_menu' => 'Open Menu',
    'btn_platforms' => 'Platforms',
    'btn_choose_service' => 'Choose Service',
    'btn_packages' => 'Packages',

    // Main menu.
    'menu_header' => 'Main Menu',
    'menu_welcome' => "👑 *WELCOME, {name_upper}!*\n\n" .
        "Hello {name}! 👋 Welcome.\n\n" .
        "I'm your partner, here to help you grow your social media accounts.\n\n" .
        "We offer fast, safe and affordable services to boost followers, likes and views 🚀📈\n\n" .
        "👇 Choose an option below:",
    'menu_new_order_title' => '🛒 Place New Order',
    'menu_new_order_desc' => 'Followers, likes or views',
    'menu_topup_title' => '💰 Add Funds',
    'menu_topup_desc' => 'Mobile Money / Card',
    'menu_profile_title' => '👤 My Profile',
    'menu_profile_desc' => 'Your balance and spending',
    'menu_referral_title' => '🎁 Invite a Friend',
    'menu_referral_desc' => 'Earn a bonus for every friend',
    'menu_track_title' => '📦 Track Order',
    'menu_track_desc' => 'Check your order progress',
    'menu_support_title' => '🎧 Customer Support',
    'menu_support_desc' => 'Talk to admin',
    'menu_settings_title' => '⚙️ Settings',
    'menu_settings_desc' => 'Lugha and currency',
    'menu_group_title' => '👥 KuzaPanel Group',
    'menu_group_desc' => 'Join for our updates',
    'menu_website_title' => '🌐 KuzaPanel Website',
    'menu_website_desc' => 'More services online',

    // Session expiry.
    'session_expired' => "⏰ Your session timed out due to inactivity.\n\nNo worries — let's start fresh! 👇",
    'awaiting_topup_confirmation' => "⏳ *WAITING FOR YOUR PAYMENT*\n\n" .
        "We haven't received confirmation of your payment yet.\n\n" .
        "📲 *If you didn't get the USSD prompt:* check your phone, or tap *Resend USSD* below.\n" .
        "✅ *If you've already paid:* please wait a few seconds — we'll message you once it's confirmed.\n\n" .
        "_Send \"#\" to return to the main menu anytime._",
    'topup_choose_action' => "⏳ *WAITING FOR YOUR PAYMENT*\n\n" .
        "We haven't received confirmation of your payment yet. What would you like to do?\n\n" .
        "📲 *Resend USSD* — get the payment prompt again\n" .
        "❌ *Cancel payment* — cancel and return to the main menu\n\n" .
        "_Or send \"#\" to return to the main menu anytime._",
    'btn_resend_ussd' => '📲 Resend USSD',
    'btn_cancel_payment' => '❌ Cancel payment',
    'payment_cancelled' => "❌ *PAYMENT CANCELLED*\n\n" .
        "No money was charged. Send \"#\" to start over anytime.",
    'topup_session_expired' => "⏰ *PAYMENT TIMED OUT*\n\n" .
        "We didn't receive confirmation of your payment in time. No money was charged.\n\n" .
        "If you'd still like to top up or place an order, let's start fresh 👇",

    // Language settings.
    'settings_choose_language' => "🌐 *CHOOSE LANGUAGE*\n\nWhich language would you like to use?",
    'language_changed' => "✅ You've switched the language to *English*.\n\nWe'll continue in English. 👇",
    'settings_press_language' => "⚠️ Please tap one of the languages above 👆\n\n_Send \"#\" to return to the main menu_",

    // AI / support.
    'ai_unavailable' => 'Sorry, the AI service is currently unavailable. Tap the button below to talk to our admin directly.',
    'ai_ask_text' => 'Please type your question in text.',
    'ai_connect_admin' => 'Sure, tap the button below to talk to our admin directly.',
    'ai_error' => "⚠️ Sorry, I couldn't get an answer right now.\n\nSend *admin* to talk to a human, or \"#\" to return to the main menu.",
    'support_ai_intro' => "🎧 *Customer Support*\n\n" .
        "Hi! I'm KuzaPanel's AI assistant. Ask me anything about our services, payments, or how to order.\n\n" .
        "💡 If you'd like to talk to an admin directly, send the word *admin*.\n" .
        "_Send \"#\" anytime to return to the main menu._",
    'support_cta' => "🎧 *Customer Support*\n\nTap the button below to talk to our admin directly.",

    // Group / website.
    'group_info' => "👥 *KuzaPanel Group*\n\nJoin our group for updates on our services and guides:\n{url}",
    'website_info' => "🌐 *KuzaPanel Website*\n\nFor many more services, faster and safer, visit our website:\n{url}",

    // Profile.
    'profile' => "👤 *MY PROFILE*\n\n" .
        "Name: {name}\n" .
        "💰 Balance: {balance} TZS\n" .
        "📊 Total Spent: {spent} TZS\n\n" .
        "Send \"#\" to return to the main menu.",

    // Referral.
    'referral_invite_text' => 'Hi! I want to grow my social media accounts. Use my code: REF {code}',
    'referral_info' => "🎁 *INVITE A FRIEND, EARN A BONUS*\n\n" .
        "Invite your friend to use KuzaPanel. When your friend makes their first deposit, " .
        "you'll earn *{percent}%* of their deposit straight into your balance! 💰\n\n" .
        "🔑 Your code: *{code}*\n" .
        "👥 Friends you've invited: {count}\n" .
        "💵 Total earned: {earnings} TZS\n\n" .
        "📲 Share this link with friends:\n{link}\n\n" .
        "Send \"#\" to return to the main menu.",
    'referral_bonus' => "🎁 *REFERRAL BONUS!*\n\n" .
        "A friend you invited just completed their first payment! 🎉\n\n" .
        "💰 You've earned a bonus of *{bonus} TZS* in your balance.\n\n" .
        "Keep inviting more friends to earn more bonuses! 🚀\n\n" .
        "© KuzaPanel",

    // Order tracking.
    'track_no_orders' => '📦 You have no orders yet. Send "#" to place a new order.',
    'track_header' => "📦 *TRACK ORDER*\n",
    'track_line' => "🆔 Order #{id} — {service}\n" .
        "   Quantity: {qty} | Cost: {amount} TZS\n" .
        "   Status: {status}",
    'track_footer' => "\nSend \"#\" to return to the main menu.",

    // Top-up.
    'topup_prompt' => "💰 *ADD FUNDS*\n\n" .
        "Please type the amount you'd like to add to your balance (TZS).\n\n" .
        "📌 Example: 5000\n\n" .
        "Send \"#\" to return to the main menu.",
    'topup_invalid' => "⚠️ That amount isn't valid.\n\nPlease type the amount you'd like to add as a number.\n📌 Example: 5000",
    'payment_sent_menu' => "{intro}\n\n👇 Choose a service to begin:",

    // Payment method / phone.
    'payment_method' => "💳 *PAYMENT METHOD*\n" .
        "💰 You need: {amount} TZS\n\n" .
        "You'll pay {amount} TZS.\n\n" .
        "Which number would you like to pay with?",
    'cancelled' => 'Cancelled. Send "#" to start over.',
    'payment_phone_prompt' => "📱 *PAYMENT NUMBER*\n\n" .
        "Please type the phone number you'll pay with (Mobile Money).\n\n" .
        "📌 Example: 0712345678\n\n" .
        "Send \"#\" to return to the main menu.",
    'payment_phone_invalid' => "⚠️ That number isn't valid.\n\nPlease type the full phone number (Mobile Money).\n📌 Example: 0712345678",
    'min_amount' => "⚠️ The minimum payment for this number is {min} TZS.\n\n" .
        "Please try again with a higher amount, or use a number from a different network.\n\n" .
        "Send \"#\" to start over.",
    'payment_failed' => "Sorry, the payment couldn't be started: {message}\nSend \"#\" to start over.",
    'topup_sent' => "🪙 We've sent a payment request!\n\n" .
        "📲 Check your phone (USSD push) and enter your PIN to confirm *{charge} TZS*.\n\n" .
        "🆔 Reference: {reference}\n\n" .
        "💰 Your balance will be topped up by {amount} TZS automatically once payment is confirmed.",
    'order_payment_sent' => "✅ *REQUEST SENT*\n\n" .
        "Amount to pay: *{charge} TZS*\n" .
        "Number: {phone}\n\n" .
        "📲 Check your phone — you'll get a payment confirmation prompt (USSD). Enter your PIN to complete.\n\n" .
        "🚀 Your order will be completed automatically once payment is confirmed.\n\n" .
        "📲 *Didn't get the USSD?* Tap *Resend USSD* below.\n" .
        "_Or send \"#\" to return to the main menu._",

    // Platform / service selection.
    'no_services' => 'Sorry, no services are available right now.',
    'platform_menu' => "📱 *CHOOSE PLATFORM*\n" .
        "Alright {name}, let's grow your account! 🚀\n\n" .
        "Which platform would you like to grow today?",
    'choose_platform' => 'Please choose a platform from the list.',
    'platform_generic_desc' => 'Various services',
    'no_services_platform' => 'Sorry, there are no services for this platform right now. Send "#" to start over.',
    'service_menu' => "🎯 *CHOOSE {platform_upper} SERVICES*\n" .
        "Great choice {name}! You picked {platform}.\n\n" .
        "What exactly does your account need? 👇",
    'price_per_1000' => '{price} TZS per 1000',
    'choose_service_reminder' => "⚠️ Please choose a service from the list above 👆\n\n_Send \"#\" to go back_",
    'service_unavailable' => 'This service is no longer available. Send "#" to start over.',

    // Quantity.
    'qty_menu' => "🔢 *CHOOSE QUANTITY*\n" .
        "How many {service} would you like to add?\n\n" .
        "Choose a package 👇",
    'qty_total' => 'Total: {total} TZS',
    'qty_custom_title' => '✏️ Custom Amount',
    'qty_custom_desc' => 'Min: {min} | Max: {max}',
    'qty_custom_prompt' => 'Please type the quantity you want (between {min} and {max}):',
    'qty_package_reminder' => "⚠️ Please choose a package from the list above 👆\n\n_Send \"#\" to go back_",
    'qty_invalid' => "That quantity isn't valid. Please type a number between {min} and {max}:",

    // Link request.
    'link_request' => "🔗 *SEND YOUR LINK*\n\n" .
        "Hi {name}, you're ordering {qty}.\n\n" .
        "{image_note}📱 Steps:\n{steps}\n\n" .
        "📌 Example: {example}\n\n" .
        "Send \"#\" to return to the main menu.",
    'link_see_image' => "👉 See the image above for the correct format.\n\n",
    'link_invalid' => "⚠️ The link you sent isn't valid.\n\n" .
        "Please send a full link starting with http:// or https://\n" .
        "📌 Example: https://instagram.com/yourname\n\n" .
        "_Send \"#\" to go back_",

    // Link instruction steps (per platform + type). Examples/images stay in code.
    'link_steps_generic' => "1️⃣ Open your {platform} account/post\n2️⃣ Tap the share icon\n3️⃣ Choose Copy link\n4️⃣ Paste the link below",
    'link_steps_instagram_profile' => "1️⃣ Open your Instagram profile\n2️⃣ Tap Share profile\n3️⃣ Choose Copy link\n4️⃣ Paste the link below",
    'link_steps_instagram_post' => "1️⃣ Open your Instagram post\n2️⃣ Tap the share icon (✈️)\n3️⃣ Choose Copy link\n4️⃣ Paste the link below",
    'link_steps_tiktok_profile' => "1️⃣ Open your TikTok account\n2️⃣ Tap the share icon at the top\n3️⃣ Choose Copy link\n4️⃣ Paste the link below",
    'link_steps_tiktok_post' => "1️⃣ Open your TikTok post\n2️⃣ Tap the share icon (✈️)\n3️⃣ Choose Copy link\n4️⃣ Paste the link below",
    'link_steps_facebook_profile' => "1️⃣ Open your Facebook account\n2️⃣ Tap the three dots\n3️⃣ Choose Copy link\n4️⃣ Paste the link below",
    'link_steps_facebook_post' => "1️⃣ Open your Facebook post\n2️⃣ Tap the share icon\n3️⃣ Choose Copy link\n4️⃣ Paste the link below",
    'link_steps_youtube_profile' => "1️⃣ Open your YouTube profile\n2️⃣ Tap Share profile\n3️⃣ Choose Copy link\n4️⃣ Paste the link below",
    'link_steps_youtube_post' => "1️⃣ Open your YouTube post\n2️⃣ Tap the share icon (✈️)\n3️⃣ Choose Copy link\n4️⃣ Paste the link below",

    // Order confirmation / completion.
    'order_confirm' => "✅ *FINAL CONFIRMATION*\n" .
        "Please review your order details:\n\n" .
        "📡 Platform: {platform}\n" .
        "🎯 Service: {service}\n" .
        "🔗 Link: {link}\n" .
        "🔢 Quantity: {qty}\n\n" .
        "💰 Cost: {amount} TZS\n\n" .
        "Shall we proceed with this order?",
    'order_cancelled' => 'Order cancelled. Send "#" to start over.',
    'wallet_debit_error' => 'Sorry, something went wrong while using your balance. Send "#" to start over.',
    'low_balance' => "⚠️ *LOW BALANCE*\n" .
        "Insufficient balance\n\n" .
        "You need an extra: {shortfall} TZS\n\n" .
        "Would you like to pay now to complete the order?",
    'order_provider_line' => "🆔 Order No: #{provider}\n",
    'order_received' => "🎉 *Order Received Successfully!*\n\n" .
        "{provider_line}⏳ We've started working on it right away! 🚀\n\n" .
        "You can track it using the 'Track Order' button in the main menu.\n\n" .
        "_Send # to return to the main menu_",

    // Deposit confirmation.
    'deposit_confirmed' => "✅ Payment of {amount} TZS has been confirmed and added to your balance!",

    // Cron order status notifications.
    'order_completed_notify' => "🎉 *ORDER COMPLETED!*\n\n" .
        "🆔 Order No: #{id}\n" .
        "🎯 Service: {service}\n" .
        "🔢 Quantity: {qty} {unit}\n" .
        "🔗 Link: {link}\n\n" .
        "✅ Your service has been delivered in full. Thank you for trusting us! 🙏\n\n" .
        "Send \"#\" to start another order.\n\n" .
        "© KuzaPanel",
    'order_cancelled_notify' => "⚠️ *ORDER CANCELLED*\n\n" .
        "🆔 Order No: #{id}\n" .
        "🎯 Service: {service}\n\n" .
        "Sorry, this order couldn't be completed. Please contact customer support for help.\n\n" .
        "Send \"#\" to return to the main menu.\n\n" .
        "© KuzaPanel",
];
