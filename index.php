<?php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = 'Home';
$bodyClass = 'page-landing';
$upcomingEvents = get_published_events(3);

$demoEvents = [
    [
        'title' => 'Sunset Mixer',
        'event_date' => date('Y-m-d', strtotime('+14 days')),
        'start_time' => '18:00:00',
        'location' => 'W Bangkok Hotel',
        'spots' => 20,
        'cover' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=200&q=80',
        'url' => base_url('events.php'),
        'badge' => 'pink',
    ],
    [
        'title' => 'Speed Dating Night',
        'event_date' => date('Y-m-d', strtotime('+21 days')),
        'start_time' => '19:00:00',
        'location' => 'EmQuartier, Bangkok',
        'spots' => 18,
        'cover' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=200&q=80',
        'url' => base_url('events.php'),
        'badge' => 'success',
    ],
    [
        'title' => 'Startup Networking',
        'event_date' => date('Y-m-d', strtotime('+30 days')),
        'start_time' => '17:30:00',
        'location' => 'Hubba Ekkamai',
        'spots' => 12,
        'cover' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=200&q=80',
        'url' => base_url('events.php'),
        'badge' => 'purple',
    ],
];

require_once APP_ROOT . '/includes/header.php';
echo render_flash();
?>

<section class="hero hero-landing">
    <div class="hero-bg hero-bg-landing"></div>
    <div class="hero-streaks" aria-hidden="true"></div>
    <div class="container hero-grid hero-grid-landing">
        <div class="hero-content">
            <div class="hero-badge">
                <svg class="hero-badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M15 4V2M15 16v-2M8 9h2M20 9h2M17.8 11.8 19 13M17.8 6.2 19 5M3 21l9-9M12.2 6.2 11 5"/></svg>
                AI-POWERED REAL-TIME MATCHING
            </div>
            <h1>Meet. Connect.<br><span class="gradient-text">Belong.</span></h1>
            <p class="hero-subtitle">Live events. Real conversations. Meaningful connections.</p>
            <div class="hero-actions">
                <a href="<?= base_url('events.php') ?>" class="btn btn-primary btn-lg">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Join an Event
                </a>
                <a href="<?= base_url('organizer/create-event.php') ?>" class="btn btn-hero-outline btn-lg">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Host Your Event
                </a>
            </div>
            <div class="hero-features">
                <div class="hero-feature">
                    <span class="hero-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="7" r="3"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><circle cx="17" cy="7" r="2.5"/><path d="M21 21v-2a3.5 3.5 0 0 0-2.5-3.36"/></svg>
                    </span>
                    AI Matching
                </div>
                <div class="hero-feature">
                    <span class="hero-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="2"/></svg>
                    </span>
                    Real-time Rounds
                </div>
                <div class="hero-feature">
                    <span class="hero-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="5" y="5" width="3" height="3" fill="currentColor" stroke="none"/><rect x="16" y="5" width="3" height="3" fill="currentColor" stroke="none"/><rect x="5" y="16" width="3" height="3" fill="currentColor" stroke="none"/><rect x="14" y="14" width="2" height="2" fill="currentColor" stroke="none"/><rect x="18" y="14" width="3" height="3" fill="currentColor" stroke="none"/><rect x="14" y="18" width="2" height="3" fill="currentColor" stroke="none"/></svg>
                    </span>
                    QR Ticketing
                </div>
                <div class="hero-feature">
                    <span class="hero-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                    </span>
                    Safe & Inclusive
                </div>
            </div>
        </div>

        <div class="hero-visual hero-visual-landing">
            <div class="match-card match-card-float">
                <div class="match-avatars">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80" alt="" class="match-avatar">
                    <div class="match-heart" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    </div>
                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80" alt="" class="match-avatar">
                </div>
                <h4>It's a match! 🎉</h4>
                <p>You both liked each other</p>
                <button type="button" class="btn btn-primary btn-sm btn-match">Start Chat</button>
            </div>
        </div>
    </div>
</section>

<section class="section section-light landing-light">
    <div class="container">
        <div class="stats-bar stats-bar-inline">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon stat-icon-purple">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6C35FF" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="stat-body">
                        <h3>10K+</h3>
                        <p>Happy Participants</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon stat-icon-pink">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FF2D8D" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="stat-body">
                        <h3>500+</h3>
                        <p>Events Hosted</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon stat-icon-purple">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6C35FF" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </div>
                    <div class="stat-body">
                        <h3>25K+</h3>
                        <p>Matches Made</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon stat-icon-pink">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FF2D8D" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div class="stat-body">
                        <h3>98%</h3>
                        <p>Would Join Again</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="landing-split-grid" id="how-it-works">
            <div class="landing-split-left">
                <div class="section-intro">
                    <h2>How It Works</h2>
                    <p>3 simple steps to meaningful connections</p>
                </div>
                <div class="steps-horizontal">
                    <div class="step-card-h">
                        <div class="step-icon step-icon-purple">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <span class="step-number">1</span>
                        <h3>Join an Event</h3>
                        <p>Choose an event that interests you and get your ticket.</p>
                    </div>
                    <div class="step-connector-h" aria-hidden="true"></div>
                    <div class="step-card-h">
                        <div class="step-icon step-icon-pink">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <span class="step-number">2</span>
                        <h3>Meet in Real-time</h3>
                        <p>Have short, fun conversations in structured rounds.</p>
                    </div>
                    <div class="step-connector-h" aria-hidden="true"></div>
                    <div class="step-card-h">
                        <div class="step-icon step-icon-violet">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        </div>
                        <span class="step-number">3</span>
                        <h3>Match Instantly</h3>
                        <p>If there's a mutual like, it's a match! Start connecting.</p>
                    </div>
                </div>
            </div>

            <div class="events-panel">
                <div class="events-panel-header">
                    <h3>Upcoming Events</h3>
                    <a href="<?= base_url('events.php') ?>" class="events-panel-link">View all</a>
                </div>
                <div class="events-panel-list">
                    <?php if ($upcomingEvents): ?>
                        <?php foreach ($upcomingEvents as $event): ?>
                            <?php
                            $cover = $event['cover_image'] ? upload_url($event['cover_image']) : default_event_image();
                            $spots = get_spots_left($event);
                            $badgeClass = $spots <= 15 ? 'badge-pink' : 'badge-success';
                            ?>
                            <a href="<?= base_url('event.php?id=' . (int) $event['id']) ?>" class="events-panel-item">
                                <img src="<?= e($cover) ?>" alt="" class="events-panel-thumb">
                                <div class="events-panel-info">
                                    <h4><?= e($event['title']) ?></h4>
                                    <p><?= e(format_date($event['event_date'])) ?> · <?= e(format_time($event['start_time'])) ?></p>
                                    <p><?= e($event['location'] ?? 'TBA') ?></p>
                                </div>
                                <span class="badge <?= $badgeClass ?>"><?= $spots ?> spots left</span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($demoEvents as $event): ?>
                            <a href="<?= e($event['url']) ?>" class="events-panel-item">
                                <img src="<?= e($event['cover']) ?>" alt="" class="events-panel-thumb">
                                <div class="events-panel-info">
                                    <h4><?= e($event['title']) ?></h4>
                                    <p><?= e(format_date($event['event_date'])) ?> · <?= e(format_time($event['start_time'])) ?></p>
                                    <p><?= e($event['location']) ?></p>
                                </div>
                                <span class="badge badge-<?= e($event['badge']) ?>"><?= (int) $event['spots'] ?> spots left</span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section-organizer" id="organizers">
    <div class="container organizer-section">
        <div class="organizer-copy">
            <span class="section-label section-label-purple">For Organizers</span>
            <h2>Run events people <span class="gradient-text">love</span></h2>
            <p>Everything you need to create, manage, and grow unforgettable events.</p>
            <div class="organizer-actions">
                <a href="<?= base_url('login.php') ?>" class="btn btn-primary btn-lg">Start Hosting</a>
                <a href="<?= base_url('index.php#pricing') ?>" class="organizer-learn-more">Learn More →</a>
            </div>
        </div>

        <div class="dashboard-mockup">
            <div class="dashboard-mockup-layout">
                <aside class="dashboard-mockup-sidebar">
                    <div class="dashboard-mockup-logo">
                        <img src="<?= brand_logo('nav') ?>" alt="Poom Connect">
                    </div>
                    <nav>
                        <span class="active">📊 Overview</span>
                        <span>📅 Events</span>
                        <span>👥 Participants</span>
                        <span>✅ Check-ins</span>
                        <span>💜 Matches</span>
                        <span>📈 Analytics</span>
                        <span>⚙️ Settings</span>
                    </nav>
                </aside>
                <div class="dashboard-mockup-main">
                    <h3 class="mockup-dashboard-title">Dashboard</h3>
                    <div class="mockup-stats-row mockup-stats-row-4">
                        <div class="mockup-stat-card">
                            <span class="mockup-stat-label">Total Events</span>
                            <strong>23</strong>
                            <span class="mockup-stat-change">+15%</span>
                        </div>
                        <div class="mockup-stat-card">
                            <span class="mockup-stat-label">Total Participants</span>
                            <strong>1,248</strong>
                            <span class="mockup-stat-change">+22%</span>
                        </div>
                        <div class="mockup-stat-card">
                            <span class="mockup-stat-label">Matches Made</span>
                            <strong>532</strong>
                            <span class="mockup-stat-change">+18%</span>
                        </div>
                        <div class="mockup-stat-card">
                            <span class="mockup-stat-label">Revenue</span>
                            <strong>฿248,000</strong>
                            <span class="mockup-stat-change">+12%</span>
                        </div>
                    </div>
                    <div class="mockup-charts-row">
                        <div class="mockup-chart-card mockup-chart-wide">
                            <h4>Participants Over Time</h4>
                            <div class="mockup-line-chart">
                                <svg viewBox="0 0 400 100" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="chartGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" stop-color="#6C35FF"/>
                                            <stop offset="100%" stop-color="#FF2D8D"/>
                                        </linearGradient>
                                        <linearGradient id="chartFill" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="rgba(108,53,255,0.18)"/>
                                            <stop offset="100%" stop-color="rgba(108,53,255,0)"/>
                                        </linearGradient>
                                    </defs>
                                    <polygon points="0,80 50,70 100,55 150,60 200,40 250,45 300,30 350,35 400,20 400,100 0,100" fill="url(#chartFill)"/>
                                    <polyline points="0,80 50,70 100,55 150,60 200,40 250,45 300,30 350,35 400,20" fill="none" stroke="url(#chartGrad)" stroke-width="3"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mockup-chart-card mockup-chart-donut">
                            <h4>Gender Balance</h4>
                            <div class="mockup-donut">
                                <div class="mockup-donut-ring"></div>
                                <div class="mockup-donut-center">52%<small>F</small></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section-dark" id="pricing">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Pricing</span>
            <h2>Simple, transparent pricing</h2>
            <p>Start free and scale as your events grow.</p>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card card">
                <h3>Starter</h3>
                <div class="pricing-price">Free</div>
                <ul class="pricing-features">
                    <li>Up to 3 events/month</li>
                    <li>50 participants per event</li>
                    <li>QR ticketing</li>
                    <li>PromptPay payments</li>
                </ul>
                <a href="<?= base_url('login.php') ?>" class="btn btn-outline btn-block">Get Started</a>
            </div>
            <div class="pricing-card card featured">
                <span class="badge badge-purple">Popular</span>
                <h3>Pro</h3>
                <div class="pricing-price">1,990 <span>THB/mo</span></div>
                <ul class="pricing-features">
                    <li>Unlimited events</li>
                    <li>500 participants per event</li>
                    <li>Live matching rounds</li>
                    <li>Analytics dashboard</li>
                    <li>Priority support</li>
                </ul>
                <a href="<?= base_url('login.php') ?>" class="btn btn-primary btn-block">Start Pro Trial</a>
            </div>
            <div class="pricing-card card">
                <h3>Enterprise</h3>
                <div class="pricing-price">Contact Us</div>
                <ul class="pricing-features">
                    <li>Custom integrations</li>
                    <li>Dedicated account manager</li>
                    <li>White-label options</li>
                    <li>SLA & premium support</li>
                </ul>
                <a href="mailto:hello@poomconnect.com" class="btn btn-outline btn-block">Contact Sales</a>
            </div>
        </div>
    </div>
</section>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
