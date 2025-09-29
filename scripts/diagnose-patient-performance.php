<?php

/**
 * Patient Endpoint Performance Diagnostic Tool
 *
 * This script helps diagnose performance issues with the doctorPatientGetAll endpoint
 * Run: php scripts/diagnose-patient-performance.php
 */

require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

class PatientPerformanceDiagnostic
{
    private $results = [];

    public function __construct()
    {
        // Bootstrap Laravel
        $app = require_once __DIR__.'/../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    }

    public function runDiagnostics()
    {
        echo "🔍 EGYAKIN Patient Endpoint Performance Diagnostic\n";
        echo "================================================\n\n";

        $this->checkDatabaseConnection();
        $this->checkTableCounts();
        $this->checkIndexes();
        $this->runPerformanceTests();
        $this->checkQueryPerformance();
        $this->generateRecommendations();

        echo "\n📊 DIAGNOSTIC COMPLETE!\n";
        echo "=======================\n";
        $this->printSummary();
    }

    private function checkDatabaseConnection()
    {
        echo "📡 Checking database connection...\n";
        try {
            DB::connection()->getPdo();
            echo "✅ Database connection: OK\n";
        } catch (Exception $e) {
            echo '❌ Database connection failed: '.$e->getMessage()."\n";
            exit(1);
        }
    }

    private function checkTableCounts()
    {
        echo "\n📊 Checking table sizes...\n";

        $tables = ['patients', 'answers', 'patient_statuses', 'users'];
        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                echo "📋 {$table}: ".number_format($count)." records\n";
                $this->results['table_counts'][$table] = $count;
            } catch (Exception $e) {
                echo "❌ Error counting {$table}: ".$e->getMessage()."\n";
            }
        }
    }

    private function checkIndexes()
    {
        echo "\n🔍 Checking database indexes...\n";

        $requiredIndexes = [
            'answers' => ['idx_answers_patient_question', 'idx_answers_question_id'],
            'patient_statuses' => ['idx_patient_statuses_patient_key', 'idx_patient_statuses_key'],
            'patients' => ['idx_patients_updated_at', 'idx_patients_doctor_hidden_updated', 'idx_patients_hidden'],
        ];

        foreach ($requiredIndexes as $table => $indexes) {
            echo "📋 Checking {$table} indexes:\n";

            try {
                $existingIndexes = collect(DB::select("SHOW INDEX FROM {$table}"))
                    ->pluck('Key_name')
                    ->unique()
                    ->toArray();

                foreach ($indexes as $requiredIndex) {
                    if (in_array($requiredIndex, $existingIndexes)) {
                        echo "  ✅ {$requiredIndex}: EXISTS\n";
                        $this->results['indexes'][$table][$requiredIndex] = true;
                    } else {
                        echo "  ❌ {$requiredIndex}: MISSING (CRITICAL)\n";
                        $this->results['indexes'][$table][$requiredIndex] = false;
                    }
                }
            } catch (Exception $e) {
                echo "  ❌ Error checking indexes for {$table}: ".$e->getMessage()."\n";
            }
        }
    }

    private function runPerformanceTests()
    {
        echo "\n⚡ Running performance tests...\n";

        // Test 1: Current query performance
        echo "🧪 Test 1: Current query performance\n";
        $this->timeQuery('Current patients query', function () {
            return DB::table('patients')
                ->select('id', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();
        });

        // Test 2: Answers query performance
        echo "🧪 Test 2: Answers query performance (N+1 simulation)\n";
        $patientIds = DB::table('patients')->limit(10)->pluck('id')->toArray();

        $this->timeQuery('Loading answers for 10 patients', function () use ($patientIds) {
            return DB::table('answers')
                ->whereIn('patient_id', $patientIds)
                ->whereIn('question_id', [1, 2])
                ->get();
        });

        // Test 3: Status query performance
        echo "🧪 Test 3: Patient status query performance\n";
        $this->timeQuery('Loading status for 10 patients', function () use ($patientIds) {
            return DB::table('patient_statuses')
                ->whereIn('patient_id', $patientIds)
                ->whereIn('key', ['submit_status', 'outcome_status'])
                ->get();
        });

        // Test 4: Optimized JOIN query
        echo "🧪 Test 4: Optimized JOIN query performance\n";
        $this->timeQuery('Optimized JOIN query', function () {
            return DB::select("
                SELECT 
                    p.id,
                    p.doctor_id,
                    p.updated_at,
                    u.name as doctor_name,
                    u.lname as doctor_lname,
                    name_answer.answer as patient_name,
                    hospital_answer.answer as patient_hospital,
                    submit_status.status as submit_status,
                    outcome_status.status as outcome_status
                FROM patients p
                LEFT JOIN users u ON p.doctor_id = u.id
                LEFT JOIN answers name_answer ON p.id = name_answer.patient_id AND name_answer.question_id = 1
                LEFT JOIN answers hospital_answer ON p.id = hospital_answer.patient_id AND hospital_answer.question_id = 2
                LEFT JOIN patient_statuses submit_status ON p.id = submit_status.patient_id AND submit_status.key = 'submit_status'
                LEFT JOIN patient_statuses outcome_status ON p.id = outcome_status.patient_id AND outcome_status.key = 'outcome_status'
                WHERE p.hidden = 0
                ORDER BY p.updated_at DESC
                LIMIT 10
            ");
        });
    }

    private function checkQueryPerformance()
    {
        echo "\n📈 Checking query execution plans...\n";

        $queries = [
            'SELECT * FROM patients WHERE hidden = 0 ORDER BY updated_at DESC LIMIT 10',
            'SELECT * FROM answers WHERE patient_id = 1 AND question_id IN (1,2)',
            "SELECT * FROM patient_statuses WHERE patient_id = 1 AND key IN ('submit_status', 'outcome_status')",
        ];

        foreach ($queries as $query) {
            echo '🔍 Analyzing: '.substr($query, 0, 50)."...\n";
            try {
                $explain = DB::select('EXPLAIN '.$query);
                foreach ($explain as $row) {
                    $type = $row->type ?? 'unknown';
                    $key = $row->key ?? 'NO INDEX';
                    $rows = $row->rows ?? 'unknown';

                    if ($key === null || $key === '') {
                        echo "  ⚠️  No index used, scanning {$rows} rows\n";
                    } else {
                        echo "  ✅ Using index '{$key}', type: {$type}, rows: {$rows}\n";
                    }
                }
            } catch (Exception $e) {
                echo '  ❌ Error analyzing query: '.$e->getMessage()."\n";
            }
        }
    }

    private function timeQuery(string $description, callable $query)
    {
        echo "  ⏱️  {$description}: ";

        $startTime = microtime(true);
        try {
            $result = $query();
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);

            $count = is_countable($result) ? count($result) : 1;
            echo "{$executionTime}ms ({$count} records)\n";

            $this->results['performance_tests'][$description] = [
                'time_ms' => $executionTime,
                'records' => $count,
            ];

            if ($executionTime > 1000) {
                echo "    ⚠️  WARNING: Query took over 1 second!\n";
            } elseif ($executionTime > 500) {
                echo "    ⚠️  SLOW: Query took over 500ms\n";
            }

        } catch (Exception $e) {
            echo '❌ FAILED: '.$e->getMessage()."\n";
        }
    }

    private function generateRecommendations()
    {
        echo "\n💡 PERFORMANCE RECOMMENDATIONS\n";
        echo "==============================\n";

        $recommendations = [];

        // Check missing indexes
        $missingIndexes = [];
        foreach ($this->results['indexes'] ?? [] as $table => $indexes) {
            foreach ($indexes as $index => $exists) {
                if (! $exists) {
                    $missingIndexes[] = "{$table}.{$index}";
                }
            }
        }

        if (! empty($missingIndexes)) {
            $recommendations[] = "🚨 CRITICAL: Add missing database indexes:\n   ".
                'Run: php artisan migrate --path=database/migrations/add_performance_indexes_patients.php';
        }

        // Check table sizes
        $answerCount = $this->results['table_counts']['answers'] ?? 0;
        $patientCount = $this->results['table_counts']['patients'] ?? 0;

        if ($answerCount > 100000) {
            $recommendations[] = "📊 Large answers table ({$answerCount} records):\n   ".
                'Consider implementing answer archiving for old patients';
        }

        if ($patientCount > 10000) {
            $recommendations[] = "📊 Large patients table ({$patientCount} records):\n   ".
                'Consider implementing soft deletes cleanup for hidden patients';
        }

        // Check slow queries
        foreach ($this->results['performance_tests'] ?? [] as $test => $result) {
            if ($result['time_ms'] > 500) {
                $recommendations[] = "⚠️  Slow query detected: {$test} ({$result['time_ms']}ms)\n   ".
                    'This needs optimization';
            }
        }

        if (empty($recommendations)) {
            echo "✅ No critical performance issues detected!\n";
        } else {
            foreach ($recommendations as $i => $recommendation) {
                echo ($i + 1).". {$recommendation}\n\n";
            }
        }
    }

    private function printSummary()
    {
        echo "\n📋 SUMMARY\n";
        echo "==========\n";

        $totalTests = count($this->results['performance_tests'] ?? []);
        $slowTests = 0;
        $totalTime = 0;

        foreach ($this->results['performance_tests'] ?? [] as $test => $result) {
            $totalTime += $result['time_ms'];
            if ($result['time_ms'] > 500) {
                $slowTests++;
            }
        }

        echo "🧪 Total tests run: {$totalTests}\n";
        echo "⚠️  Slow queries: {$slowTests}\n";
        echo '⏱️  Total execution time: '.round($totalTime, 2)."ms\n";

        $missingIndexCount = 0;
        foreach ($this->results['indexes'] ?? [] as $table => $indexes) {
            foreach ($indexes as $exists) {
                if (! $exists) {
                    $missingIndexCount++;
                }
            }
        }

        echo "🔍 Missing indexes: {$missingIndexCount}\n";

        if ($missingIndexCount > 0 || $slowTests > 0) {
            echo "\n🚨 ACTION REQUIRED: Performance issues detected!\n";
            echo "1. Run the database migration to add indexes\n";
            echo "2. Use the OptimizedPatientFilterService\n";
            echo "3. Monitor query performance after changes\n";
        } else {
            echo "\n✅ Performance looks good!\n";
        }
    }
}

// Run the diagnostic
$diagnostic = new PatientPerformanceDiagnostic();
$diagnostic->runDiagnostics();
