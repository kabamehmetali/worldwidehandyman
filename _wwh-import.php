<?php
/**
 * One-time SEO content importer — TEMPORARY. Delete this file once the import
 * has run. It exists only because this host has shell access disabled, so the
 * seed cannot be applied with the mysql client.
 *
 *   https://yourdomain.com/_wwh-import.php?key=<token below>
 *
 * It runs sql/seo.sql (creates the three seo_* tables and their settings) and
 * then sql/seo-seed.sql (50 location pages, 26 service pages, 120 service x
 * city pages), using the site's own database credentials from includes/config.php.
 *
 * It will not touch anything else: CREATE DATABASE and USE statements are
 * stripped so it can only ever act on the database the site is already
 * connected to, and the two filenames are hard-coded.
 */

declare(strict_types=1);

const IMPORT_KEY = 'wwh-seo-import-2026';

header('X-Robots-Tag: noindex, nofollow');
header('Content-Type: text/plain; charset=utf-8');

if (($_GET['key'] ?? '') !== IMPORT_KEY) {
    http_response_code(404);
    exit("Not found.\n");
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

/**
 * Split a SQL file into statements, respecting quoted strings and escapes so
 * that semicolons inside page copy do not chop a statement in half.
 */
function sql_statements(string $sql): array
{
    $statements = [];
    $buffer     = '';
    $inSingle   = false;
    $inDouble   = false;
    $inBacktick = false;
    $escaped    = false;
    $length     = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];

        if ($escaped) {
            $buffer .= $char;
            $escaped = false;
            continue;
        }
        if ($char === '\\' && ($inSingle || $inDouble)) {
            $buffer .= $char;
            $escaped = true;
            continue;
        }

        // Line comments, only when outside a string. MySQL treats "--" as a
        // comment when it is followed by whitespace OR ends the line, so a
        // bare "--" separator line counts too.
        if (!$inSingle && !$inDouble && !$inBacktick) {
            $isComment = $char === '#';
            if (!$isComment && $char === '-' && substr($sql, $i, 2) === '--') {
                $next = $sql[$i + 2] ?? "\n";
                $isComment = ($next === ' ' || $next === "\t" || $next === "\n" || $next === "\r");
            }
            if ($isComment) {
                $newline = strpos($sql, "\n", $i);
                if ($newline === false) {
                    break;
                }
                $i = $newline;
                $buffer .= "\n";
                continue;
            }
        }

        if ($char === "'" && !$inDouble && !$inBacktick) {
            $inSingle = !$inSingle;
        } elseif ($char === '"' && !$inSingle && !$inBacktick) {
            $inDouble = !$inDouble;
        } elseif ($char === '`' && !$inSingle && !$inDouble) {
            $inBacktick = !$inBacktick;
        }

        if ($char === ';' && !$inSingle && !$inDouble && !$inBacktick) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }
    return $statements;
}

function run_file(string $path): void
{
    if (!is_file($path)) {
        echo "  MISSING: {$path}\n";
        echo "  (make sure sql/ was included in the deployment)\n";
        return;
    }

    $sql = (string) file_get_contents($path);
    $statements = sql_statements($sql);

    $ran = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($statements as $statement) {
        // Never let this script pick a database or create one — it may only
        // operate on the connection the site itself already uses.
        if (preg_match('~^\s*(?:USE|CREATE\s+DATABASE)\b~i', $statement)) {
            $skipped++;
            continue;
        }
        try {
            db()->exec($statement);
            $ran++;
        } catch (PDOException $e) {
            $failed++;
            if ($failed <= 5) {
                echo "  ERROR: " . $e->getMessage() . "\n";
                echo "         in: " . substr(preg_replace('/\s+/', ' ', $statement), 0, 140) . "\n";
            }
        }
    }

    printf("  %d statements run, %d skipped (USE/CREATE DATABASE), %d failed\n", $ran, $skipped, $failed);
}

echo "Worldwide Handyman — SEO content import\n";
echo "=======================================\n\n";
echo "Database: " . DB_NAME . " on " . DB_HOST . "\n\n";

echo "sql/seo.sql (tables + settings)\n";
run_file(__DIR__ . '/sql/seo.sql');

echo "\nsql/seo-seed.sql (page content)\n";
run_file(__DIR__ . '/sql/seo-seed.sql');

echo "\nResult\n------\n";
foreach ([
    'seo_locations'         => 'location pages',
    'seo_services'          => 'service pages',
    'seo_service_locations' => 'service x city pages',
] as $table => $label) {
    try {
        $count = (int) db()->query("SELECT COUNT(*) FROM {$table} WHERE is_published = 1")->fetchColumn();
        printf("  %-22s %4d %s\n", $table, $count, $label);
    } catch (PDOException $e) {
        printf("  %-22s  ??  (%s)\n", $table, $e->getMessage());
    }
}

echo "\nNow delete _wwh-import.php and _wwh-diag.php from the server.\n";
