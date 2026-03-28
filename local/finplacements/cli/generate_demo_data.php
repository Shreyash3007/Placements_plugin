<?php
/**
 * CLI script to generate rich demo data for FinTree Placements.
 *
 * This populates the job board with 25+ realistic fintech/finance openings
 * with logos and skill-rich descriptions to test weighted search and AJAX.
 *
 * Usage: php local/finplacements/cli/generate_demo_data.php
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/finplacements/lib.php');

list($options, $unrecognized) = cli_get_params(
    ['help' => false, 'purge' => true],
    ['h' => 'help', 'p' => 'purge']
);

if ($options['help']) {
    $help = "Generate demo-data for FinTree Placements.
Options:
-h, --help      Print this help.
-p, --purge     Purge existing openings/applications first (default: true).
";
    cli_writeln($help);
    exit(0);
}

cli_heading('FinTree Placements — Demo Data Generator');

if ($options['purge']) {
    cli_writeln('Purging existing records...');
    $DB->delete_records('finplacements_applications');
    $DB->delete_records('finplacements_openings');
    cli_writeln('Deleted all data.');
}

$admin = $DB->get_record('user', ['username' => 'admin'], '*', MUST_EXIST);

$openings_data = [
    [
        'title'      => 'Equity Research Analyst (Metals & Mining)',
        'company'    => 'Goldman Sachs',
        'logo'       => 'https://logo.clearbit.com/goldmansachs.com',
        'location'   => 'Mumbai',
        'type'       => 'fulltime',
        'exp'        => '1-3',
        'cfa'        => 'level2',
        'min_ctc'    => '18',
        'max_ctc'    => '25',
        'jd'         => 'We are looking for an Equity Analyst to cover the Metals sector. Strong Financial Modeling and Valuation skills required. Knowledge of Python for data extraction is a plus.',
    ],
    [
        'title'      => 'Associate - Investment Banking',
        'company'    => 'Morgan Stanley',
        'logo'       => 'https://logo.clearbit.com/morganstanley.com',
        'location'   => 'Bengaluru',
        'type'       => 'fulltime',
        'exp'        => '3-5',
        'cfa'        => 'charter',
        'min_ctc'    => '28',
        'max_ctc'    => '45',
        'jd'         => 'High-stakes role in IB. Master of Financial Modeling and LBO analysis needed. Should have CFA Charter.',
    ],
    [
        'title'      => 'Finance Intern (FinTech Strategy)',
        'company'    => 'FinTree Education',
        'location'   => 'Remote',
        'type'       => 'internship',
        'exp'        => 'fresher',
        'cfa'        => 'level1',
        'stipend'    => '15000',
        'jd'         => 'Help our strategy team scale the platform. Passion for FinTech and learning Valuation basics.',
    ],
    [
        'title'      => 'Risk Management Specialist',
        'company'    => 'HDFC Bank',
        'logo'       => 'https://logo.clearbit.com/hdfcbank.com',
        'location'   => 'Chennai',
        'type'       => 'fulltime',
        'exp'        => '5-10',
        'frm'        => 'part2',
        'min_ctc'    => '22',
        'jd'         => 'Credit Risk and Market Risk modeling for private banking. FRM Part 2 is mandatory.',
    ],
    [
        'title'      => 'Valuation Intern',
        'company'    => 'Deloitte',
        'logo'       => 'https://logo.clearbit.com/deloitte.com',
        'location'   => 'Mumbai',
        'type'       => 'internship',
        'exp'        => 'fresher',
        'cfa'        => 'none',
        'stipend'    => '25000',
        'jd'         => 'DCF valuation support for M&A team. Fast-paced environment.',
    ],
    [
        'title'      => 'Python Developer - Quant Desk',
        'company'    => 'Tower Research',
        'location'   => 'Gurugram',
        'type'       => 'fulltime',
        'exp'        => '1-3',
        'min_ctc'    => '35',
        'jd'         => 'Building algorithmic trading pipelines. Must be high-proficiency in Python and Pandas.',
    ],
];

// Fill up to 25 records with slightly varied data for pagination testing
for ($i = 0; $i < 20; $i++) {
    $cities = ['Mumbai', 'Delhi', 'Bengaluru', 'Chennai', 'Remote'];
    $companies = ['BlackRock', 'J.P. Morgan', 'UBS', 'Citibank', 'KPMG'];
    $city = $cities[array_rand($cities)];
    $company = $companies[array_rand($companies)];
    
    $openings_data[] = [
        'title'      => "Financial Analyst - Cycle $i",
        'company'    => $company,
        'location'   => $city,
        'type'       => ($i % 2 == 0) ? 'fulltime' : 'internship',
        'exp'        => ($i % 3 == 0) ? '1-3' : 'fresher',
        'cfa'        => 'level1',
        'min_ctc'    => ($i % 2 == 0) ? (10 + $i) : null,
        'stipend'    => ($i % 2 != 0) ? (10000 + ($i * 100)) : null,
        'jd'         => "Standard role for $company in $city. Requires basic accounting and Python skills.",
    ];
}

cli_writeln('Inserting records...');

foreach ($openings_data as $data) {
    $record = new \stdClass();
    $record->title = $data['title'];
    $record->company = $data['company'];
    $record->company_logo_url = $data['logo'] ?? '';
    $record->location = $data['location'];
    $record->employment_type = $data['type'];
    $record->experience_required = $data['exp'];
    $record->min_ctc = $data['min_ctc'] ?? null;
    $record->max_ctc = $data['max_ctc'] ?? null;
    $record->stipend = $data['stipend'] ?? null;
    $record->cfa_required = $data['cfa'] ?? 'none';
    $record->frm_required = $data['frm'] ?? 'none';
    $record->jd_text = $data['jd'];
    $record->status = 'active';
    $record->deadline = time() + (86400 * rand(5, 30));
    $record->timecreated = time() - (86400 * rand(1, 15));
    $record->createdby = $admin->id;
    
    $id = \local_finplacements\opening_manager::create($record, $admin->id);
    cli_writeln("- Created: {$record->title} at {$record->company} (ID: $id)");
}

cli_writeln('Success! Demo data ready.');
