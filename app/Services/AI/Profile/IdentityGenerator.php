<?php

namespace App\Services\AI\Profile;

use Carbon\Carbon;

class IdentityGenerator
{
    /**
     * Generate complete identity for agent
     */
    public function generateIdentity(string $country, string $personality): array
    {
        $age = $this->generateAge($personality);
        $gender = rand(0, 1) ? 'male' : 'female';
        
        return [
            'name' => $this->generateName($country, $gender),
            'username' => null, // Will be generated from name
            'age' => $age,
            'date_of_birth' => $this->generateDateOfBirth($age),
            'gender' => $gender,
            'city' => $this->generateCity($country),
            'bio' => $this->generateBio($personality, $country, $age),
            'interests' => $this->generateInterests($personality),
            'profession' => $this->generateProfession($personality),
            'political_leaning' => $this->generatePoliticalLeaning($country, $personality),
            'writing_style' => $this->generateWritingStyle($personality),
            'editorial_tone' => $this->generateEditorialTone($personality),
        ];
    }

    /**
     * Generate realistic age based on personality
     */
    protected function generateAge(string $personality): int
    {
        $ageRanges = [
            'political' => [30, 55], // Older, more experienced
            'sports' => [22, 40], // Younger, more energetic
            'tech' => [24, 45], // Tech-savvy age range
            'entertainment' => [22, 50], // Wide range
            'general' => [25, 50], // Balanced
        ];

        $range = $ageRanges[$personality] ?? [25, 50];
        return rand($range[0], $range[1]);
    }

    /**
     * Generate date of birth from age
     */
    protected function generateDateOfBirth(int $age): string
    {
        $year = now()->year - $age;
        $month = rand(1, 12);
        $day = rand(1, 28);
        
        return Carbon::create($year, $month, $day)->format('Y-m-d');
    }

    /**
     * Generate culture-specific name
     */
    protected function generateName(string $country, string $gender): string
    {
        $names = [
            'IN' => [
                'male' => ['Ravi Kumar', 'Amit Sharma', 'Rajesh Patel', 'Vikram Singh', 'Arjun Verma', 'Sanjay Gupta', 'Rahul Mehta', 'Anil Kumar', 'Suresh Reddy', 'Karan Malhotra'],
                'female' => ['Priya Sharma', 'Anjali Singh', 'Neha Patel', 'Pooja Verma', 'Kavita Gupta', 'Sunita Reddy', 'Ritu Mehta', 'Deepa Kumar', 'Swati Malhotra', 'Meera Joshi'],
            ],
            'US' => [
                'male' => ['John Smith', 'Michael Johnson', 'David Williams', 'Robert Brown', 'James Davis', 'William Miller', 'Richard Wilson', 'Joseph Moore', 'Thomas Taylor', 'Christopher Anderson'],
                'female' => ['Mary Johnson', 'Jennifer Smith', 'Linda Williams', 'Patricia Brown', 'Elizabeth Davis', 'Barbara Miller', 'Susan Wilson', 'Jessica Moore', 'Sarah Taylor', 'Karen Anderson'],
            ],
            'GB' => [
                'male' => ['James Wilson', 'Oliver Smith', 'Harry Johnson', 'George Brown', 'Jack Taylor', 'Charlie Davies', 'Thomas Evans', 'Henry Roberts', 'William Turner', 'Edward Clarke'],
                'female' => ['Emily Smith', 'Olivia Johnson', 'Amelia Brown', 'Isla Taylor', 'Ava Davies', 'Mia Evans', 'Grace Roberts', 'Sophie Turner', 'Charlotte Clarke', 'Lily Walker'],
            ],
            'PK' => [
                'male' => ['Ahmed Khan', 'Ali Hassan', 'Muhammad Raza', 'Usman Ahmed', 'Bilal Shah', 'Hamza Ali', 'Imran Khan', 'Faisal Malik', 'Tariq Hussain', 'Asad Iqbal'],
                'female' => ['Fatima Khan', 'Ayesha Hassan', 'Zainab Ahmed', 'Sana Ali', 'Maryam Shah', 'Hira Malik', 'Nida Hussain', 'Rabia Iqbal', 'Amna Raza', 'Saira Butt'],
            ],
            'AU' => [
                'male' => ['Jack Thompson', 'William Anderson', 'Oliver Williams', 'James Brown', 'Noah Wilson', 'Liam Taylor', 'Mason Davies', 'Lucas Martin', 'Ethan White', 'Alexander Lee'],
                'female' => ['Olivia Thompson', 'Charlotte Anderson', 'Amelia Williams', 'Mia Brown', 'Ava Wilson', 'Emily Taylor', 'Isla Davies', 'Grace Martin', 'Sophie White', 'Chloe Lee'],
            ],
        ];

        $countryNames = $names[$country] ?? $names['US'];
        $genderNames = $countryNames[$gender] ?? $countryNames['male'];
        
        return $genderNames[array_rand($genderNames)];
    }

    /**
     * Generate username from name
     */
    public function generateUsername(string $name): string
    {
        $base = strtolower(str_replace(' ', '_', $name));
        $base = preg_replace('/[^a-z0-9_]/', '', $base) ?: 'news';
        $base = trim(preg_replace('/_+/', '_', $base), '_');
        $base = str_replace('agent', '', $base);
        $base = trim(preg_replace('/_+/', '_', $base), '_');
        if ($base === '') {
            $base = 'news';
        }

        $suffix = rand(0, 1) ? '_ai' : '_bot';
        return $base . $suffix . rand(10, 99);
    }

    /**
     * Generate city based on country
     */
    protected function generateCity(string $country): string
    {
        $cities = [
            'IN' => ['Mumbai', 'Delhi', 'Bangalore', 'Hyderabad', 'Chennai', 'Kolkata', 'Pune', 'Ahmedabad', 'Jaipur', 'Lucknow'],
            'US' => ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'Austin'],
            'GB' => ['London', 'Manchester', 'Birmingham', 'Leeds', 'Glasgow', 'Liverpool', 'Newcastle', 'Sheffield', 'Bristol', 'Edinburgh'],
            'PK' => ['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta', 'Sialkot', 'Gujranwala'],
            'AU' => ['Sydney', 'Melbourne', 'Brisbane', 'Perth', 'Adelaide', 'Gold Coast', 'Canberra', 'Newcastle', 'Wollongong', 'Hobart'],
            'CA' => ['Toronto', 'Vancouver', 'Montreal', 'Calgary', 'Edmonton', 'Ottawa', 'Winnipeg', 'Quebec City', 'Hamilton', 'Victoria'],
        ];

        $countryCities = $cities[$country] ?? ['Capital City', 'Main City', 'Central City'];
        return $countryCities[array_rand($countryCities)];
    }

    /**
     * Generate profession based on personality
     */
    protected function generateProfession(string $personality): string
    {
        $professions = [
            'political' => ['Journalist', 'Political Analyst', 'Activist', 'Lawyer', 'Public Policy student', 'Retired Civil Servant'],
            'sports' => ['Sports Coach', 'Athlete', 'Physical Trainer', 'Sports Journalist', 'Student', 'Gym Owner'],
            'tech' => ['Software Engineer', 'Product Manager', 'Tech Blogger', 'Startup Founder', 'Data Scientist', 'IT Consultant'],
            'entertainment' => ['Filmmaker', 'Musician', 'Content Creator', 'Actor', 'Critic', 'Fashion Designer'],
            'general' => ['Teacher', 'Doctor', 'Small Business Owner', 'Accountant', 'Student', 'Nurse', 'Chef', 'Driver'],
        ];

        $pool = $professions[$personality] ?? $professions['general'];
        return $pool[array_rand($pool)];
    }

    /**
     * Generate political leaning
     */
    protected function generatePoliticalLeaning(string $country, string $personality): string
    {
        // Country specific nuances could be added here
        $leanings = ['Left', 'Right', 'Center-Left', 'Center-Right', 'Center', 'Anti-Establishment', 'Libertarian', 'Socialist'];
        
        if ($personality === 'political') {
            // Political accounts are rarely centrist
            $leanings = ['Far Left', 'Far Right', 'Socialist', 'Conservative', 'Liberal', 'Nationalist'];
        }

        return $leanings[array_rand($leanings)];
    }

    /**
     * Generate writing style
     */
    protected function generateWritingStyle(string $personality): string
    {
        $styles = [
            'Short & Punchy', 'Long & Analytical', 'Emoji Heavy', 'Formal & Academic', 'Casual & Slang', 'Question Heavy', 'Statistical'
        ];
        
        return $styles[array_rand($styles)];
    }

    /**
     * Generate editorial tone
     */
    protected function generateEditorialTone(string $personality): string
    {
        $tones = [
            'political' => ['Aggressive', 'Analytical', 'Sarcastic', 'Conspiracy Theorist', 'Optimist', 'Pessimist'],
            'sports' => ['Passionate', 'Analytical', 'Aggressive (Fan)', 'Optimist'],
            'tech' => ['Analytical', 'Optimist (Futurist)', 'Sarcastic (Critic)', 'Helpful'],
            'entertainment' => ['Excited', 'Critical', 'Sarcastic', 'Emotional'],
            'general' => ['Neutral Reporter', 'Emotional', 'Sarcastic', 'Optimist', 'Pessimist'],
        ];

        $pool = $tones[$personality] ?? $tones['general'];
        return $pool[array_rand($pool)];
    }

    /**
     * Generate personality-based bio (Updated for Part 1)
     */
    protected function generateBio(string $personality, string $country, int $age): string
    {
        $templates = [
            'Always watching the world, one update at a time.',
            'Signals over noise, clarity over chaos.',
            'New day, fresh context, straight to the point.',
            'I track what matters and keep it moving.',
            'Headlines change fast, so do I.',
            'Calm voice, sharp focus, steady updates.',
            'News never sleeps. Neither do I.',
        ];

        return $templates[array_rand($templates)];
    }

    /**
     * Generate a tagline based on new attributes
     */
    protected function generateIdentityTagline(string $personality): string
    {
        // This adds flavor based on the sophisticated attributes
        $taglines = [
            'political' => [
                'Unapologetically {leaning}.', 
                '{tone} takes on today\'s news.',
                'Analyzing the chaos.',
                'Speaking truth to power.',
            ],
            'sports' => [
                '{tone} commentary always.',
                'Stats don\'t lie.',
                'Pure passion, no filter.',
            ],
            'tech' => [
                'Building the future.',
                '{tone} look at tech.',
                'Code is law.',
            ],
            'entertainment' => [
                'All the drama.',
                '{tone} reviews.',
                'Pop culture vulture.',
            ],
            'general' => [
                'Just my {tone} opinion.',
                'Observing life.',
                'Living in the moment.',
            ],
        ];

        $pool = $taglines[$personality] ?? $taglines['general'];
        $tagline = $pool[array_rand($pool)];
        
        // We can replace placeholders here if we had access to the generated attributes in this method
        // For now, let's keep it simple
        return str_replace(['{leaning}', '{tone}'], [
            $this->generatePoliticalLeaning('US', $personality), // Quick hack, should pass actual
            $this->generateEditorialTone($personality)
        ], $tagline);
    }

    /**
     * Generate interests based on personality
     */
    protected function generateInterests(string $personality): array
    {
        $interestPool = [
            'political' => ['Politics', 'Current Affairs', 'History', 'Economics', 'Governance', 'Elections', 'Policy', 'Democracy', 'International Relations', 'Social Issues'],
            'sports' => ['Cricket', 'Football', 'Basketball', 'Tennis', 'Fitness', 'Olympics', 'Sports News', 'Team Stats', 'Player Analysis', 'Game Strategy'],
            'tech' => ['Technology', 'AI', 'Gadgets', 'Programming', 'Startups', 'Innovation', 'Cybersecurity', 'Cloud Computing', 'Mobile Apps', 'Gaming'],
            'entertainment' => ['Movies', 'Music', 'TV Shows', 'Celebrities', 'Awards', 'Concerts', 'Streaming', 'Pop Culture', 'Fashion', 'Art'],
            'general' => ['News', 'Travel', 'Food', 'Photography', 'Reading', 'Writing', 'Nature', 'Philosophy', 'Science', 'Culture'],
        ];

        $pool = $interestPool[$personality] ?? $interestPool['general'];
        $count = rand(5, 10);
        
        shuffle($pool);
        return array_slice($pool, 0, $count);
    }
}
