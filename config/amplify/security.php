<?php

return [
    'force_password_reset_enabled' => true,
    'password_length' => 4,
    'cookie_title' => 'We value your privacy',
    'cookie_content' => <<<'HTML'
                <p>
                    This website stores cookies on your computer. These cookies are used to improve your website experience and provide more personalized services to you, both on this website and through other media. To find out more about the cookies we use, see our Privacy Policy.
                    <br>
                    <br>
                    We won't track your information when you visit our site. But in order to comply with your preferences, we'll have to use just one tiny cookie so that you're not asked to make this choice again.
                </p>
HTML,
    'skip_contact_approval' => true,
    'verification_method' => 'email'
];
