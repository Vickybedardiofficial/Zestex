<?php

namespace App\Services\AI\ProfileGenerator;

class NameGenerator
{
    protected array $names = [
        'IN' => [
            'political' => [
                'male' => [
                    'first' => ['Ravi', 'Rajesh', 'Amit', 'Suresh', 'Vijay', 'Anil', 'Manoj', 'Sanjay'],
                    'last' => ['Kumar', 'Sharma', 'Singh', 'Verma', 'Gupta', 'Yadav', 'Patel', 'Mishra']
                ],
                'female' => [
                    'first' => ['Priya', 'Sunita', 'Kavita', 'Anjali', 'Neha', 'Pooja', 'Rekha', 'Meera'],
                    'last' => ['Sharma', 'Singh', 'Verma', 'Gupta', 'Patel', 'Yadav', 'Mishra', 'Reddy']
                ]
            ],
            'sports' => [
                'male' => [
                    'first' => ['Rohit', 'Virat', 'Sachin', 'Dhoni', 'Yuvraj', 'Hardik', 'Rishabh', 'Shikhar'],
                    'last' => ['Sharma', 'Kohli', 'Tendulkar', 'Dhoni', 'Singh', 'Pandya', 'Pant', 'Dhawan']
                ],
                'female' => [
                    'first' => ['Mithali', 'Smriti', 'Harmanpreet', 'Jhulan', 'Deepti', 'Shafali', 'Poonam'],
                    'last' => ['Raj', 'Mandhana', 'Kaur', 'Goswami', 'Sharma', 'Verma', 'Yadav']
                ]
            ],
            'tech' => [
                'male' => [
                    'first' => ['Arjun', 'Karan', 'Rohan', 'Aditya', 'Varun', 'Nikhil', 'Rahul', 'Ankit'],
                    'last' => ['Mehta', 'Agarwal', 'Jain', 'Shah', 'Kapoor', 'Malhotra', 'Chopra', 'Khanna']
                ],
                'female' => [
                    'first' => ['Shreya', 'Divya', 'Isha', 'Nidhi', 'Riya', 'Sakshi', 'Tanvi', 'Aditi'],
                    'last' => ['Mehta', 'Agarwal', 'Jain', 'Shah', 'Kapoor', 'Malhotra', 'Chopra', 'Khanna']
                ]
            ],
            'entertainment' => [
                'male' => [
                    'first' => ['Aamir', 'Salman', 'Shah Rukh', 'Akshay', 'Hrithik', 'Ranbir', 'Ranveer'],
                    'last' => ['Khan', 'Kumar', 'Kapoor', 'Roshan', 'Singh', 'Patel', 'Sharma']
                ],
                'female' => [
                    'first' => ['Deepika', 'Priyanka', 'Katrina', 'Alia', 'Kareena', 'Anushka', 'Vidya'],
                    'last' => ['Padukone', 'Chopra', 'Kaif', 'Bhatt', 'Kapoor', 'Sharma', 'Balan']
                ]
            ],
            'general' => [
                'male' => [
                    'first' => ['Rajesh', 'Sunil', 'Prakash', 'Mahesh', 'Ramesh', 'Dinesh', 'Mukesh'],
                    'last' => ['Kumar', 'Sharma', 'Singh', 'Verma', 'Gupta', 'Patel', 'Yadav']
                ],
                'female' => [
                    'first' => ['Sunita', 'Geeta', 'Seema', 'Rekha', 'Usha', 'Lata', 'Savita'],
                    'last' => ['Sharma', 'Singh', 'Verma', 'Gupta', 'Patel', 'Yadav', 'Mishra']
                ]
            ]
        ],
        'US' => [
            'political' => [
                'male' => [
                    'first' => ['John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Thomas'],
                    'last' => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller']
                ],
                'female' => [
                    'first' => ['Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan'],
                    'last' => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller']
                ]
            ],
            'sports' => [
                'male' => [
                    'first' => ['LeBron', 'Tom', 'Aaron', 'Patrick', 'Stephen', 'Kevin', 'James'],
                    'last' => ['James', 'Brady', 'Rodgers', 'Mahomes', 'Curry', 'Durant', 'Harden']
                ],
                'female' => [
                    'first' => ['Serena', 'Simone', 'Megan', 'Alex', 'Sue', 'Diana', 'Candace'],
                    'last' => ['Williams', 'Biles', 'Rapinoe', 'Morgan', 'Bird', 'Taurasi', 'Parker']
                ]
            ],
            'tech' => [
                'male' => [
                    'first' => ['Mark', 'Elon', 'Jeff', 'Bill', 'Steve', 'Larry', 'Sundar'],
                    'last' => ['Zuckerberg', 'Musk', 'Bezos', 'Gates', 'Jobs', 'Page', 'Pichai']
                ],
                'female' => [
                    'first' => ['Sheryl', 'Susan', 'Marissa', 'Ginni', 'Safra', 'Whitney', 'Meg'],
                    'last' => ['Sandberg', 'Wojcicki', 'Mayer', 'Rometty', 'Catz', 'Wolfe', 'Whitman']
                ]
            ],
            'entertainment' => [
                'male' => [
                    'first' => ['Brad', 'Tom', 'Leonardo', 'Johnny', 'Will', 'Denzel', 'Robert'],
                    'last' => ['Pitt', 'Cruise', 'DiCaprio', 'Depp', 'Smith', 'Washington', 'Downey']
                ],
                'female' => [
                    'first' => ['Jennifer', 'Angelina', 'Scarlett', 'Emma', 'Meryl', 'Nicole', 'Julia'],
                    'last' => ['Aniston', 'Jolie', 'Johansson', 'Stone', 'Streep', 'Kidman', 'Roberts']
                ]
            ],
            'general' => [
                'male' => [
                    'first' => ['James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard'],
                    'last' => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis']
                ],
                'female' => [
                    'first' => ['Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan'],
                    'last' => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis']
                ]
            ]
        ],
        'PK' => [
            'political' => [
                'male' => [
                    'first' => ['Ahmed', 'Ali', 'Hassan', 'Usman', 'Bilal', 'Faisal', 'Imran'],
                    'last' => ['Khan', 'Ahmed', 'Ali', 'Hassan', 'Malik', 'Sheikh', 'Butt']
                ],
                'female' => [
                    'first' => ['Fatima', 'Ayesha', 'Zainab', 'Maryam', 'Hina', 'Sana', 'Nida'],
                    'last' => ['Khan', 'Ahmed', 'Ali', 'Hassan', 'Malik', 'Sheikh', 'Butt']
                ]
            ],
            'sports' => [
                'male' => [
                    'first' => ['Babar', 'Shaheen', 'Mohammad', 'Shadab', 'Hasan', 'Fakhar', 'Imad'],
                    'last' => ['Azam', 'Afridi', 'Rizwan', 'Khan', 'Ali', 'Zaman', 'Wasim']
                ],
                'female' => [
                    'first' => ['Bismah', 'Nida', 'Javeria', 'Sana', 'Diana', 'Aliya', 'Fatima'],
                    'last' => ['Maroof', 'Dar', 'Khan', 'Mir', 'Baig', 'Riaz', 'Sana']
                ]
            ],
            'general' => [
                'male' => [
                    'first' => ['Muhammad', 'Ahmed', 'Ali', 'Hassan', 'Usman', 'Bilal', 'Faisal'],
                    'last' => ['Khan', 'Ahmed', 'Ali', 'Hassan', 'Malik', 'Sheikh', 'Butt']
                ],
                'female' => [
                    'first' => ['Fatima', 'Ayesha', 'Zainab', 'Maryam', 'Hina', 'Sana', 'Nida'],
                    'last' => ['Khan', 'Ahmed', 'Ali', 'Hassan', 'Malik', 'Sheikh', 'Butt']
                ]
            ]
        ],
        'GB' => [
            'political' => [
                'male' => [
                    'first' => ['James', 'Oliver', 'Harry', 'George', 'William', 'Charles', 'Thomas'],
                    'last' => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Wilson', 'Taylor']
                ],
                'female' => [
                    'first' => ['Emma', 'Olivia', 'Amelia', 'Isla', 'Ava', 'Emily', 'Isabella'],
                    'last' => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Wilson', 'Taylor']
                ]
            ],
            'general' => [
                'male' => [
                    'first' => ['James', 'Oliver', 'Harry', 'George', 'William', 'Jack', 'Thomas'],
                    'last' => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Wilson', 'Taylor']
                ],
                'female' => [
                    'first' => ['Emma', 'Olivia', 'Amelia', 'Isla', 'Ava', 'Emily', 'Isabella'],
                    'last' => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Wilson', 'Taylor']
                ]
            ]
        ]
    ];

    public function generateName(string $country = 'IN', string $personality = 'general', string $gender = null): array
    {
        // Auto-detect gender if not provided
        if (!$gender) {
            $gender = rand(0, 1) ? 'male' : 'female';
        }

        // Get names for country and personality
        $countryNames = $this->names[$country] ?? $this->names['IN'];
        $personalityNames = $countryNames[$personality] ?? $countryNames['general'];
        $genderNames = $personalityNames[$gender] ?? $personalityNames['male'];

        // Random selection
        $firstName = $genderNames['first'][array_rand($genderNames['first'])];
        $lastName = $genderNames['last'][array_rand($genderNames['last'])];

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $firstName . ' ' . $lastName,
            'gender' => $gender
        ];
    }

    public function generateUsername(string $firstName, string $lastName, string $personality): string
    {
        $base = strtolower($firstName . '_' . $lastName);
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
}
