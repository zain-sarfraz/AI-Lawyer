<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'case_number' => fn (Blueprint $t) => $t->string('case_number')->nullable()->after('title'),
            'neutral_citation' => fn (Blueprint $t) => $t->string('neutral_citation')->nullable()->after('citation'),
            'other_citations' => fn (Blueprint $t) => $t->json('other_citations')->nullable()->after('neutral_citation'),
            'party_one_name' => fn (Blueprint $t) => $t->string('party_one_name')->nullable()->after('case_type'),
            'party_one_role' => fn (Blueprint $t) => $t->string('party_one_role')->nullable()->after('party_one_name'),
            'party_two_name' => fn (Blueprint $t) => $t->string('party_two_name')->nullable()->after('party_one_role'),
            'party_two_role' => fn (Blueprint $t) => $t->string('party_two_role')->nullable()->after('party_two_name'),
            'parties_json' => fn (Blueprint $t) => $t->json('parties_json')->nullable()->after('party_two_role'),
            'bench_json' => fn (Blueprint $t) => $t->json('bench_json')->nullable()->after('parties_json'),
            'author_judge' => fn (Blueprint $t) => $t->string('author_judge')->nullable()->after('bench_json'),
            'filing_date' => fn (Blueprint $t) => $t->date('filing_date')->nullable()->after('author_judge'),
            'judgment_url' => fn (Blueprint $t) => $t->string('judgment_url')->nullable()->after('source_url'),
            'pdf_url' => fn (Blueprint $t) => $t->string('pdf_url')->nullable()->after('judgment_url'),
            'is_reported' => fn (Blueprint $t) => $t->boolean('is_reported')->nullable()->after('pdf_url'),
            'winner_label' => fn (Blueprint $t) => $t->string('winner_label')->nullable()->after('winner'),
            'disposition' => fn (Blueprint $t) => $t->string('disposition')->nullable()->after('winner_label'),
            'final_order' => fn (Blueprint $t) => $t->text('final_order')->nullable()->after('disposition'),
            'laws_applied_json' => fn (Blueprint $t) => $t->json('laws_applied_json')->nullable()->after('final_order'),
            'legal_issues_json' => fn (Blueprint $t) => $t->json('legal_issues_json')->nullable()->after('laws_applied_json'),
            'party_one_evidence_json' => fn (Blueprint $t) => $t->json('party_one_evidence_json')->nullable()->after('legal_issues_json'),
            'party_two_evidence_json' => fn (Blueprint $t) => $t->json('party_two_evidence_json')->nullable()->after('party_one_evidence_json'),
            'court_reasoning_json' => fn (Blueprint $t) => $t->json('court_reasoning_json')->nullable()->after('party_two_evidence_json'),
            'cited_cases_json' => fn (Blueprint $t) => $t->json('cited_cases_json')->nullable()->after('court_reasoning_json'),
            'keywords_json' => fn (Blueprint $t) => $t->json('keywords_json')->nullable()->after('cited_cases_json'),
            'topic' => fn (Blueprint $t) => $t->string('topic')->nullable()->after('keywords_json'),
            'ai_model' => fn (Blueprint $t) => $t->string('ai_model')->nullable()->after('ai_confidence'),
            'ai_extracted_at' => fn (Blueprint $t) => $t->timestamp('ai_extracted_at')->nullable()->after('ai_model'),
            'ai_notes' => fn (Blueprint $t) => $t->text('ai_notes')->nullable()->after('ai_status'),
            'is_demo' => fn (Blueprint $t) => $t->boolean('is_demo')->default(false)->after('ai_notes'),
        ];

        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('case_law_records', $name)) {
                Schema::table('case_law_records', function (Blueprint $table) use ($definition) {
                    $definition($table);
                });
            }
        }

        $this->ensureIndex('case_law_records', 'case_law_records_disposition_index', 'disposition');
        $this->ensureIndex('case_law_records', 'case_law_records_neutral_citation_index', 'neutral_citation');
        $this->ensureIndex('case_law_records', 'case_law_records_is_demo_index', 'is_demo');

        if (Schema::hasColumn('case_law_records', 'is_demo')) {
            DB::table('case_law_records')->where('source', 'demo')->update(['is_demo' => true]);
        }
    }

    public function down(): void
    {
        $drop = [
            'case_number', 'neutral_citation', 'other_citations',
            'party_one_name', 'party_one_role', 'party_two_name', 'party_two_role', 'parties_json',
            'bench_json', 'author_judge', 'filing_date',
            'judgment_url', 'pdf_url', 'is_reported',
            'winner_label', 'disposition', 'final_order',
            'laws_applied_json', 'legal_issues_json',
            'party_one_evidence_json', 'party_two_evidence_json',
            'court_reasoning_json', 'cited_cases_json', 'keywords_json', 'topic',
            'ai_model', 'ai_extracted_at', 'ai_notes', 'is_demo',
        ];

        Schema::table('case_law_records', function (Blueprint $table) use ($drop) {
            foreach ($drop as $column) {
                if (Schema::hasColumn('case_law_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function ensureIndex(string $table, string $indexName, string $column): void
    {
        $exists = collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]))->isNotEmpty();
        if ($exists || ! Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $indexName) {
            $blueprint->index($column, $indexName);
        });
    }
};
