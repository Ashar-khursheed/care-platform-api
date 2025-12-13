<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use App\Models\User;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user as author
        $admin = User::where('email', 'admin@careplatform.com')->first();
        
        if (!$admin) {
            $this->command->error('Admin user not found. Please run user seeder first.');
            return;
        }

        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => $this->getAboutUsContent(),
                'excerpt' => 'Learn about Care Platform, our mission, and how we connect families with trusted care providers.',
                'is_published' => true,
                'show_in_menu' => true,
                'menu_order' => 1,
                'author_id' => $admin->id,
                'meta_title' => 'About Us - Care Platform',
                'meta_description' => 'Learn about Care Platform, our mission to connect families with trusted care providers, and how we ensure safety and quality.',
                'meta_keywords' => 'about care platform, our mission, care services',
                'published_at' => now(),
            ],
            [
                'title' => 'How It Works',
                'slug' => 'how-it-works',
                'content' => $this->getHowItWorksContent(),
                'excerpt' => 'Discover how easy it is to find and book trusted care providers on Care Platform.',
                'is_published' => true,
                'show_in_menu' => true,
                'menu_order' => 2,
                'author_id' => $admin->id,
                'meta_title' => 'How It Works - Care Platform',
                'meta_description' => 'Learn how to find, book, and manage care services on Care Platform. Simple steps to connect with trusted providers.',
                'published_at' => now(),
            ],
            [
                'title' => 'Safety & Trust',
                'slug' => 'safety',
                'content' => $this->getSafetyContent(),
                'excerpt' => 'Your safety is our priority. Learn about our verification process and safety measures.',
                'is_published' => true,
                'show_in_menu' => true,
                'menu_order' => 3,
                'author_id' => $admin->id,
                'meta_title' => 'Safety & Trust - Care Platform',
                'meta_description' => 'Learn about our comprehensive safety measures, background checks, and verification process for all care providers.',
                'published_at' => now(),
            ],
            [
                'title' => 'Terms and Conditions',
                'slug' => 'terms-and-conditions',
                'content' => $this->getTermsContent(),
                'excerpt' => 'Read our terms and conditions for using Care Platform.',
                'is_published' => true,
                'show_in_menu' => false,
                'menu_order' => 10,
                'author_id' => $admin->id,
                'meta_title' => 'Terms and Conditions - Care Platform',
                'meta_description' => 'Read the terms and conditions for using Care Platform services.',
                'published_at' => now(),
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => $this->getPrivacyContent(),
                'excerpt' => 'Learn how we protect your privacy and handle your personal information.',
                'is_published' => true,
                'show_in_menu' => false,
                'menu_order' => 11,
                'author_id' => $admin->id,
                'meta_title' => 'Privacy Policy - Care Platform',
                'meta_description' => 'Learn about our privacy practices and how we protect your personal information on Care Platform.',
                'published_at' => now(),
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact',
                'content' => $this->getContactContent(),
                'excerpt' => 'Get in touch with our support team. We\'re here to help!',
                'is_published' => true,
                'show_in_menu' => true,
                'menu_order' => 4,
                'author_id' => $admin->id,
                'meta_title' => 'Contact Us - Care Platform',
                'meta_description' => 'Contact Care Platform support team. Email, phone, or visit our office. We\'re here to help with your questions.',
                'published_at' => now(),
            ],
            [
                'title' => 'FAQs',
                'slug' => 'faqs',
                'content' => $this->getFaqsContent(),
                'excerpt' => 'Find answers to frequently asked questions about Care Platform.',
                'is_published' => true,
                'show_in_menu' => true,
                'menu_order' => 5,
                'author_id' => $admin->id,
                'meta_title' => 'Frequently Asked Questions - Care Platform',
                'meta_description' => 'Find answers to common questions about using Care Platform, booking services, and becoming a provider.',
                'published_at' => now(),
            ],
        ];

        foreach ($pages as $pageData) {
            Page::create($pageData);
        }

        $this->command->info('Default pages created successfully!');
    }

    protected function getAboutUsContent()
    {
        return '<h1>About Care Platform</h1>

<p>Welcome to Care Platform, your trusted marketplace for finding quality care services. Founded in 2024, we\'ve made it our mission to connect families and individuals with reliable, verified care providers across multiple categories.</p>

<h2>Our Mission</h2>
<p>We believe everyone deserves access to trusted care services. Whether you need childcare, senior care, pet care, or home services, Care Platform makes it easy to find, book, and manage professional care providers in your area.</p>

<h2>What Makes Us Different</h2>
<ul>
<li><strong>Verified Providers:</strong> All providers undergo thorough background checks and verification</li>
<li><strong>Transparent Reviews:</strong> Real reviews from real families help you make informed decisions</li>
<li><strong>Secure Payments:</strong> Book and pay safely through our platform</li>
<li><strong>24/7 Support:</strong> Our customer support team is always here to help</li>
</ul>

<h2>Our Values</h2>
<p><strong>Safety First:</strong> Your safety and security are our top priorities. We maintain strict verification standards for all providers.</p>

<p><strong>Community:</strong> We\'re building a community of caregivers and families who support each other.</p>

<p><strong>Trust:</strong> Transparency and honesty guide everything we do.</p>

<p><strong>Quality:</strong> We\'re committed to connecting you with the best care providers.</p>

<h2>Join Our Community</h2>
<p>Whether you\'re looking for care or offering your services, Care Platform is here to help you succeed. Join thousands of families and providers who trust Care Platform every day.</p>';
    }

    protected function getHowItWorksContent()
    {
        return '<h1>How It Works</h1>

<p>Finding and booking trusted care providers has never been easier. Follow these simple steps to get started with Care Platform.</p>

<h2>For Families</h2>

<h3>1. Search for Services</h3>
<p>Browse our categories or search for specific care services in your area. Use filters to narrow down by availability, rating, experience, and more.</p>

<h3>2. Review Profiles</h3>
<p>Read detailed provider profiles, check reviews from other families, view certifications, and see availability calendars.</p>

<h3>3. Book Securely</h3>
<p>Select your preferred date and time, confirm the booking, and pay securely through our platform. Your payment is protected until the service is completed.</p>

<h3>4. Get Care</h3>
<p>Meet your provider, receive quality care, and stay connected through our messaging system.</p>

<h3>5. Leave a Review</h3>
<p>Share your experience to help other families make informed decisions.</p>

<h2>For Providers</h2>

<h3>1. Create Your Profile</h3>
<p>Sign up, complete verification, add your qualifications, experience, and availability.</p>

<h3>2. Get Discovered</h3>
<p>Families in your area will find you when searching for care services. Your profile will appear in relevant searches.</p>

<h3>3. Accept Bookings</h3>
<p>Receive booking requests, review the details, and accept jobs that fit your schedule.</p>

<h3>4. Provide Care</h3>
<p>Meet your client, provide exceptional service, and build your reputation through positive reviews.</p>

<h3>5. Get Paid</h3>
<p>Receive secure payments directly to your account after completing each booking.</p>

<h2>Safety & Support</h2>
<p>Our team is available 24/7 to help with any questions or concerns. All providers are verified and background-checked for your peace of mind.</p>';
    }

    protected function getSafetyContent()
    {
        return '<h1>Safety & Trust</h1>

<p>At Care Platform, safety is our highest priority. We\'ve implemented comprehensive measures to ensure you can book care services with confidence.</p>

<h2>Provider Verification</h2>

<h3>Background Checks</h3>
<p>Every provider undergoes a thorough background check including:</p>
<ul>
<li>Criminal record check</li>
<li>Identity verification</li>
<li>Reference checks</li>
<li>Qualification verification</li>
</ul>

<h3>Certification Verification</h3>
<p>We verify all professional certifications, licenses, and qualifications claimed by providers. This includes:</p>
<ul>
<li>CPR and First Aid certification</li>
<li>Professional licenses (where applicable)</li>
<li>Educational credentials</li>
<li>Training certificates</li>
</ul>

<h2>Secure Platform</h2>

<h3>Safe Payments</h3>
<p>All payments are processed through secure, encrypted channels. We hold payments in escrow until services are completed to protect both parties.</p>

<h3>Identity Protection</h3>
<p>Your personal information is protected with bank-level encryption. We never share your data without permission.</p>

<h3>Secure Messaging</h3>
<p>Communicate with providers through our secure in-app messaging system. Your contact information stays private until you choose to share it.</p>

<h2>Community Safety</h2>

<h3>Reviews & Ratings</h3>
<p>Read honest reviews from verified users. Our review system helps maintain quality and transparency across the platform.</p>

<h3>Report System</h3>
<p>Quickly report any concerns or inappropriate behavior. Our team investigates all reports promptly.</p>

<h3>24/7 Support</h3>
<p>Our support team is available around the clock to address safety concerns and provide assistance.</p>

<h2>Trust Standards</h2>
<p>We maintain strict standards for all providers:</p>
<ul>
<li>Regular re-verification of credentials</li>
<li>Ongoing monitoring of ratings and reviews</li>
<li>Immediate action on reported concerns</li>
<li>Continuous improvement of safety measures</li>
</ul>

<h2>Your Role in Safety</h2>
<p>While we do our part, your safety also depends on good judgment:</p>
<ul>
<li>Read provider profiles and reviews carefully</li>
<li>Communicate clearly about expectations</li>
<li>Trust your instincts</li>
<li>Report any concerns immediately</li>
</ul>

<p>Together, we create a safe, trustworthy community for care services.</p>';
    }

    protected function getTermsContent()
    {
        return '<h1>Terms and Conditions</h1>

<p><strong>Last Updated:</strong> December 2024</p>

<p>Welcome to Care Platform. By accessing or using our services, you agree to be bound by these Terms and Conditions.</p>

<h2>1. Acceptance of Terms</h2>
<p>By creating an account and using Care Platform, you accept and agree to be bound by these Terms and Conditions and our Privacy Policy.</p>

<h2>2. User Accounts</h2>

<h3>2.1 Account Creation</h3>
<p>You must provide accurate, current, and complete information during registration. You are responsible for maintaining the confidentiality of your account credentials.</p>

<h3>2.2 User Types</h3>
<p>Care Platform supports two types of users:</p>
<ul>
<li><strong>Clients:</strong> Individuals seeking care services</li>
<li><strong>Providers:</strong> Individuals offering care services</li>
</ul>

<h2>3. Service Provider Requirements</h2>

<h3>3.1 Verification</h3>
<p>All service providers must complete our verification process, including background checks and document verification.</p>

<h3>3.2 Professional Conduct</h3>
<p>Providers must maintain professional standards, honor bookings, and provide services as described in their profiles.</p>

<h2>4. Bookings and Payments</h2>

<h3>4.1 Booking Process</h3>
<p>Clients can book services through our platform. Bookings are subject to provider acceptance.</p>

<h3>4.2 Payment Terms</h3>
<p>Payments are processed securely through our platform. We collect a service fee from each transaction.</p>

<h3>4.3 Cancellation Policy</h3>
<p>Cancellations are subject to our cancellation policy. Fees may apply for late cancellations.</p>

<h2>5. User Conduct</h2>

<p>Users must not:</p>
<ul>
<li>Violate any laws or regulations</li>
<li>Provide false information</li>
<li>Harass, abuse, or harm others</li>
<li>Circumvent platform fees</li>
<li>Share account credentials</li>
</ul>

<h2>6. Intellectual Property</h2>
<p>All content on Care Platform, including text, graphics, logos, and software, is our property and protected by copyright laws.</p>

<h2>7. Limitation of Liability</h2>
<p>Care Platform acts as a marketplace connecting users. We are not liable for the actions of users or the quality of services provided.</p>

<h2>8. Indemnification</h2>
<p>Users agree to indemnify Care Platform against any claims arising from their use of the platform or violation of these terms.</p>

<h2>9. Dispute Resolution</h2>
<p>Disputes between users should first be resolved through our platform\'s resolution process. Legal disputes will be subject to arbitration.</p>

<h2>10. Modifications</h2>
<p>We reserve the right to modify these terms at any time. Users will be notified of significant changes.</p>

<h2>11. Termination</h2>
<p>We may suspend or terminate accounts that violate these terms or engage in harmful behavior.</p>

<h2>12. Contact</h2>
<p>For questions about these terms, contact us at legal@careplatform.com</p>';
    }

    protected function getPrivacyContent()
    {
        return '<h1>Privacy Policy</h1>

<p><strong>Last Updated:</strong> December 2024</p>

<p>Care Platform ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, and protect your personal information.</p>

<h2>1. Information We Collect</h2>

<h3>1.1 Information You Provide</h3>
<ul>
<li>Account information (name, email, phone number)</li>
<li>Profile details (bio, photo, qualifications)</li>
<li>Payment information</li>
<li>Communications with us and other users</li>
<li>Reviews and ratings</li>
</ul>

<h3>1.2 Automatically Collected Information</h3>
<ul>
<li>Device and browser information</li>
<li>IP address and location data</li>
<li>Usage data and analytics</li>
<li>Cookies and similar technologies</li>
</ul>

<h2>2. How We Use Your Information</h2>

<p>We use your information to:</p>
<ul>
<li>Provide and improve our services</li>
<li>Process bookings and payments</li>
<li>Verify user identity and credentials</li>
<li>Communicate with you about bookings and updates</li>
<li>Ensure platform safety and prevent fraud</li>
<li>Personalize your experience</li>
<li>Comply with legal obligations</li>
</ul>

<h2>3. Information Sharing</h2>

<h3>3.1 With Other Users</h3>
<p>Profile information is visible to other users to facilitate bookings. Contact information is shared only after booking confirmation.</p>

<h3>3.2 With Service Providers</h3>
<p>We share information with third-party service providers who help us operate our platform (payment processors, hosting services, etc.).</p>

<h3>3.3 Legal Requirements</h3>
<p>We may disclose information when required by law or to protect our rights and users\' safety.</p>

<h2>4. Data Security</h2>

<p>We implement industry-standard security measures:</p>
<ul>
<li>Encryption of sensitive data</li>
<li>Secure payment processing</li>
<li>Regular security audits</li>
<li>Access controls and authentication</li>
</ul>

<h2>5. Your Rights and Choices</h2>

<p>You have the right to:</p>
<ul>
<li>Access your personal information</li>
<li>Correct inaccurate data</li>
<li>Delete your account and data</li>
<li>Opt-out of marketing communications</li>
<li>Export your data</li>
</ul>

<h2>6. Cookies and Tracking</h2>

<p>We use cookies to:</p>
<ul>
<li>Remember your preferences</li>
<li>Analyze platform usage</li>
<li>Improve user experience</li>
<li>Provide personalized content</li>
</ul>

<p>You can control cookies through your browser settings.</p>

<h2>7. Children\'s Privacy</h2>
<p>Our platform is not intended for users under 18. We do not knowingly collect information from minors.</p>

<h2>8. Data Retention</h2>
<p>We retain your information as long as your account is active or as needed to provide services. You can request deletion at any time.</p>

<h2>9. International Data Transfers</h2>
<p>Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place.</p>

<h2>10. Changes to This Policy</h2>
<p>We may update this Privacy Policy periodically. We\'ll notify you of significant changes via email or platform notification.</p>

<h2>11. Contact Us</h2>
<p>For privacy-related questions or requests, contact us at:</p>
<ul>
<li>Email: privacy@careplatform.com</li>
<li>Address: 123 Main Street, New York, NY 10001</li>
<li>Phone: +1 (555) 123-4567</li>
</ul>';
    }

    protected function getContactContent()
    {
        return '<h1>Contact Us</h1>

<p>Have questions? Need help? We\'re here for you! Our support team is available 24/7 to assist with any questions or concerns.</p>

<h2>Get in Touch</h2>

<h3>Email Support</h3>
<p><strong>General Inquiries:</strong> support@careplatform.com<br>
<strong>Provider Support:</strong> providers@careplatform.com<br>
<strong>Account Issues:</strong> accounts@careplatform.com<br>
<strong>Safety Concerns:</strong> safety@careplatform.com</p>

<h3>Phone Support</h3>
<p><strong>Main Line:</strong> +1 (555) 123-4567<br>
<strong>Toll-Free:</strong> 1-800-CARE-PLT<br>
Available 24/7</p>

<h3>Office Address</h3>
<p>Care Platform Headquarters<br>
123 Main Street<br>
New York, NY 10001<br>
United States</p>

<h3>Business Hours</h3>
<p>Our offices are open:<br>
Monday - Friday: 9:00 AM - 6:00 PM EST<br>
Saturday: 10:00 AM - 4:00 PM EST<br>
Sunday: Closed (Phone support available 24/7)</p>

<h2>Online Support</h2>

<h3>Live Chat</h3>
<p>Chat with our support team directly through the platform. Look for the chat icon in the bottom right corner.</p>

<h3>Help Center</h3>
<p>Visit our comprehensive Help Center for articles, guides, and FAQs.</p>

<h3>Community Forum</h3>
<p>Join our community forum to connect with other users and share experiences.</p>

<h2>Social Media</h2>
<p>Follow us for updates, tips, and community stories:</p>
<ul>
<li>Facebook: facebook.com/careplatform</li>
<li>Twitter: @careplatform</li>
<li>Instagram: @careplatform</li>
<li>LinkedIn: linkedin.com/company/careplatform</li>
</ul>

<h2>Feedback</h2>
<p>We value your feedback! Share your suggestions for improving Care Platform at feedback@careplatform.com</p>

<h2>Media Inquiries</h2>
<p>For press and media inquiries, contact our PR team at press@careplatform.com</p>

<h2>Partnership Opportunities</h2>
<p>Interested in partnering with Care Platform? Reach out to partnerships@careplatform.com</p>';
    }

    protected function getFaqsContent()
    {
        return '<h1>Frequently Asked Questions</h1>

<h2>General Questions</h2>

<h3>What is Care Platform?</h3>
<p>Care Platform is a marketplace that connects families and individuals with trusted, verified care providers across multiple categories including childcare, senior care, pet care, housekeeping, and more.</p>

<h3>Is Care Platform free to use?</h3>
<p>Creating an account and browsing providers is free. We charge a small service fee on completed bookings to maintain and improve our platform.</p>

<h3>How do I know providers are trustworthy?</h3>
<p>All providers undergo thorough background checks, identity verification, and credential verification. You can also read reviews from other families before booking.</p>

<h2>For Clients</h2>

<h3>How do I book a service?</h3>
<p>Browse providers, select one you like, choose your preferred date and time, and confirm your booking. Payment is processed securely through our platform.</p>

<h3>Can I cancel a booking?</h3>
<p>Yes, you can cancel bookings according to our cancellation policy. Fees may apply for late cancellations.</p>

<h3>How do payments work?</h3>
<p>Payments are processed securely when you book. Funds are held in escrow until the service is completed, then released to the provider.</p>

<h3>What if I\'m not satisfied with the service?</h3>
<p>Contact our support team immediately. We have a resolution process to address concerns and, when appropriate, issue refunds.</p>

<h2>For Providers</h2>

<h3>How do I become a provider?</h3>
<p>Sign up, complete your profile, upload required documents, and pass our verification process. Once approved, you can start receiving bookings.</p>

<h3>How do I get paid?</h3>
<p>Payments are automatically transferred to your account after completing each booking, minus our service fee.</p>

<h3>Can I set my own rates?</h3>
<p>Yes, you have full control over your pricing. You can adjust rates based on services offered, experience, and market demand.</p>

<h3>How do I get more bookings?</h3>
<p>Maintain a complete profile, respond quickly to inquiries, provide excellent service, and encourage satisfied clients to leave reviews.</p>

<h2>Safety & Security</h2>

<h3>Is my payment information secure?</h3>
<p>Yes, all payments are processed through encrypted, PCI-compliant payment processors. We never store your complete payment information.</p>

<h3>How is my privacy protected?</h3>
<p>We use industry-standard security measures to protect your data. Read our Privacy Policy for detailed information.</p>

<h3>What if I have a safety concern?</h3>
<p>Contact our safety team immediately at safety@careplatform.com or through the in-app reporting feature. All concerns are investigated promptly.</p>

<h2>Technical Questions</h2>

<h3>Is there a mobile app?</h3>
<p>Yes, Care Platform is available on iOS and Android. Download from the App Store or Google Play.</p>

<h3>How do I reset my password?</h3>
<p>Click "Forgot Password" on the login page and follow the instructions sent to your email.</p>

<h3>Why can\'t I log in?</h3>
<p>Ensure you\'re using the correct email and password. If you\'re still having trouble, use the password reset feature or contact support.</p>

<h2>Still Have Questions?</h2>
<p>Contact our support team 24/7:</p>
<ul>
<li>Email: support@careplatform.com</li>
<li>Phone: +1 (555) 123-4567</li>
<li>Live Chat: Available on our website and app</li>
</ul>';
    }
}