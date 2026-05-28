<?php

return [
    'countries' => [
        // Asia
        'IN' => ['name' => 'India', 'timezone' => 'Asia/Kolkata', 'languages' => ['en-IN', 'hi-IN']],
        'PK' => ['name' => 'Pakistan', 'timezone' => 'Asia/Karachi', 'languages' => ['en-PK', 'ur-PK']],
        'BD' => ['name' => 'Bangladesh', 'timezone' => 'Asia/Dhaka', 'languages' => ['en-BD', 'bn-BD']],
        'CN' => ['name' => 'China', 'timezone' => 'Asia/Shanghai', 'languages' => ['zh-CN']],
        'JP' => ['name' => 'Japan', 'timezone' => 'Asia/Tokyo', 'languages' => ['ja-JP']],
        'KR' => ['name' => 'South Korea', 'timezone' => 'Asia/Seoul', 'languages' => ['ko-KR']],
        'ID' => ['name' => 'Indonesia', 'timezone' => 'Asia/Jakarta', 'languages' => ['id-ID']],
        'MY' => ['name' => 'Malaysia', 'timezone' => 'Asia/Kuala_Lumpur', 'languages' => ['ms-MY', 'en-MY']],
        'SG' => ['name' => 'Singapore', 'timezone' => 'Asia/Singapore', 'languages' => ['en-SG']],
        'TH' => ['name' => 'Thailand', 'timezone' => 'Asia/Bangkok', 'languages' => ['th-TH']],
        'VN' => ['name' => 'Vietnam', 'timezone' => 'Asia/Ho_Chi_Minh', 'languages' => ['vi-VN']],
        'PH' => ['name' => 'Philippines', 'timezone' => 'Asia/Manila', 'languages' => ['en-PH', 'tl-PH']],
        'AE' => ['name' => 'UAE', 'timezone' => 'Asia/Dubai', 'languages' => ['ar-AE', 'en-AE']],
        'SA' => ['name' => 'Saudi Arabia', 'timezone' => 'Asia/Riyadh', 'languages' => ['ar-SA']],
        'IL' => ['name' => 'Israel', 'timezone' => 'Asia/Jerusalem', 'languages' => ['he-IL', 'en-IL']],
        'TR' => ['name' => 'Turkey', 'timezone' => 'Europe/Istanbul', 'languages' => ['tr-TR']],
        
        // Europe
        'GB' => ['name' => 'United Kingdom', 'timezone' => 'Europe/London', 'languages' => ['en-GB']],
        'DE' => ['name' => 'Germany', 'timezone' => 'Europe/Berlin', 'languages' => ['de-DE']],
        'FR' => ['name' => 'France', 'timezone' => 'Europe/Paris', 'languages' => ['fr-FR']],
        'IT' => ['name' => 'Italy', 'timezone' => 'Europe/Rome', 'languages' => ['it-IT']],
        'ES' => ['name' => 'Spain', 'timezone' => 'Europe/Madrid', 'languages' => ['es-ES']],
        'NL' => ['name' => 'Netherlands', 'timezone' => 'Europe/Amsterdam', 'languages' => ['nl-NL']],
        'PL' => ['name' => 'Poland', 'timezone' => 'Europe/Warsaw', 'languages' => ['pl-PL']],
        'RU' => ['name' => 'Russia', 'timezone' => 'Europe/Moscow', 'languages' => ['ru-RU']],
        'UA' => ['name' => 'Ukraine', 'timezone' => 'Europe/Kiev', 'languages' => ['uk-UA']],
        'SE' => ['name' => 'Sweden', 'timezone' => 'Europe/Stockholm', 'languages' => ['sv-SE']],
        'NO' => ['name' => 'Norway', 'timezone' => 'Europe/Oslo', 'languages' => ['no-NO']],
        'DK' => ['name' => 'Denmark', 'timezone' => 'Europe/Copenhagen', 'languages' => ['da-DK']],
        'FI' => ['name' => 'Finland', 'timezone' => 'Europe/Helsinki', 'languages' => ['fi-FI']],
        'IE' => ['name' => 'Ireland', 'timezone' => 'Europe/Dublin', 'languages' => ['en-IE']],
        'PT' => ['name' => 'Portugal', 'timezone' => 'Europe/Lisbon', 'languages' => ['pt-PT']],
        'GR' => ['name' => 'Greece', 'timezone' => 'Europe/Athens', 'languages' => ['el-GR']],
        
        // Americas
        'US' => ['name' => 'United States', 'timezone' => 'America/New_York', 'languages' => ['en-US']],
        'CA' => ['name' => 'Canada', 'timezone' => 'America/Toronto', 'languages' => ['en-CA', 'fr-CA']],
        'MX' => ['name' => 'Mexico', 'timezone' => 'America/Mexico_City', 'languages' => ['es-MX']],
        'BR' => ['name' => 'Brazil', 'timezone' => 'America/Sao_Paulo', 'languages' => ['pt-BR']],
        'AR' => ['name' => 'Argentina', 'timezone' => 'America/Buenos_Aires', 'languages' => ['es-AR']],
        'CL' => ['name' => 'Chile', 'timezone' => 'America/Santiago', 'languages' => ['es-CL']],
        'CO' => ['name' => 'Colombia', 'timezone' => 'America/Bogota', 'languages' => ['es-CO']],
        'PE' => ['name' => 'Peru', 'timezone' => 'America/Lima', 'languages' => ['es-PE']],
        'VE' => ['name' => 'Venezuela', 'timezone' => 'America/Caracas', 'languages' => ['es-VE']],
        
        // Africa
        'ZA' => ['name' => 'South Africa', 'timezone' => 'Africa/Johannesburg', 'languages' => ['en-ZA']],
        'NG' => ['name' => 'Nigeria', 'timezone' => 'Africa/Lagos', 'languages' => ['en-NG']],
        'EG' => ['name' => 'Egypt', 'timezone' => 'Africa/Cairo', 'languages' => ['ar-EG']],
        'KE' => ['name' => 'Kenya', 'timezone' => 'Africa/Nairobi', 'languages' => ['en-KE', 'sw-KE']],
        'GH' => ['name' => 'Ghana', 'timezone' => 'Africa/Accra', 'languages' => ['en-GH']],
        'ET' => ['name' => 'Ethiopia', 'timezone' => 'Africa/Addis_Ababa', 'languages' => ['am-ET']],
        
        // Oceania
        'AU' => ['name' => 'Australia', 'timezone' => 'Australia/Sydney', 'languages' => ['en-AU']],
        'NZ' => ['name' => 'New Zealand', 'timezone' => 'Pacific/Auckland', 'languages' => ['en-NZ']],
    ],
    
    'news_sources' => [
        // Global
        'global' => [
            'BBC News' => 'http://feeds.bbci.co.uk/news/rss.xml',
            'CNN' => 'http://rss.cnn.com/rss/edition.rss',
            'Reuters' => 'https://www.reutersagency.com/feed/',
            'Al Jazeera' => 'https://www.aljazeera.com/xml/rss/all.xml',
            'News UN' => 'https://news.un.org/feed/subscribe/en/news/all/rss.xml',
            'The Economist' => 'https://www.economist.com/the-world-this-week/rss.xml',
            'Forbes' => 'https://www.forbes.com/real-time/feed2/',
            'CTBTO' => 'https://www.ctbto.org/rss.xml',
            'SCMP' => 'https://www.scmp.com/rss/91/feed',
            'EWeek' => 'https://www.eweek.com/feed/',
            'EurAsian Times' => 'https://www.eurasiantimes.com/feed/',
        ],
        
        // India
        'IN' => [
            'Times of India' => 'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
            'NDTV' => 'https://feeds.feedburner.com/ndtvnews-top-stories',
            'The Hindu' => 'https://www.thehindu.com/news/national/feeder/default.rss',
            'India Today' => 'https://www.indiatoday.in/rss/home',
            'Indian Express' => 'https://indianexpress.com/feed/',
            'Indian Expres' => 'https://indianexpress.com/feed/',
            'Greater Kashmir' => 'https://www.greaterkashmir.com/feed/',
            'Business Standard' => 'https://www.business-standard.com/rss/home_page_top_stories.rss',
            'WION' => 'https://www.wionews.com/rss/india-news',
            'Firstpost' => 'https://www.firstpost.com/commonfeeds/v1/mfp/rss/world.xml',
            'LiveMint' => 'https://www.livemint.com/rss/news',
            'ThePrint' => 'https://theprint.in/feed/',
        ],
        
        // US
        'US' => [
            'New York Times' => 'https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml',
            'Washington Post' => 'https://feeds.washingtonpost.com/rss/world',
            'Fox News' => 'https://moxie.foxnews.com/google-publisher/latest.xml',
            'Forbes US' => 'https://www.forbes.com/real-time/feed2/',
        ],
        
        // UK
        'GB' => [
            'The Guardian' => 'https://www.theguardian.com/uk/rss',
            'Daily Mail' => 'https://www.dailymail.co.uk/news/index.rss',
            'Telegraph' => 'https://www.telegraph.co.uk/rss.xml',
        ],
        
        // Pakistan
        'PK' => [
            'Dawn' => 'https://www.dawn.com/feeds/home',
            'Express Tribune' => 'https://tribune.com.pk/feed/home',
        ],
        
        'AU' => [
            'ABC News' => 'https://www.abc.net.au/news/feed/51120/rss.xml',
            'Sydney Morning Herald' => 'https://www.smh.com.au/rss/feed.xml',
        ],

        // China
        'CN' => [
            'South China Morning Post' => 'https://www.scmp.com/rss/91/feed',
            'China Daily' => 'http://www.chinadaily.com.cn/rss/china_rss.xml',
        ],

        // Russia
        'RU' => [
            'The Moscow Times' => 'https://www.themoscowtimes.com/rss/news',
        ],

        // France
        'FR' => [
            'France 24' => 'https://www.france24.com/en/rss',
            'Le Monde (English)' => 'https://www.lemonde.fr/en/rss/full.xml',
        ],

        // Germany
        'DE' => [
            'Deutsche Welle' => 'https://rss.dw.com/rdf/rss-en-all',
        ],

        // Japan
        'JP' => [
            'NHK World' => 'https://www3.nhk.or.jp/nhkworld/en/news/list.xml',
            'Japan Times' => 'https://www.japantimes.co.jp/feed',
        ],
    ],
];
