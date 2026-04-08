/**
 * Analysis type definitions.
 *
 * TypeScript types for SEO analysis results matching the AnalysisResultDTO
 * and AnalysisResultResource API response shapes.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

/**
 * Union of the 8 built-in analyzer names.
 */
export type AnalyzerName =
    | 'content_length'
    | 'focus_keyword'
    | 'heading_structure'
    | 'image_alt'
    | 'internal_link'
    | 'keyword_density'
    | 'meta_length'
    | 'readability';

/**
 * Analysis grade based on overall score.
 */
export type AnalysisGrade = 'good' | 'ok' | 'poor';

/**
 * Analysis grade color for display.
 */
export type AnalysisGradeColor = 'green' | 'yellow' | 'red';

/**
 * Status of an individual analyzer check.
 */
export type AnalyzerStatus = 'pass' | 'warning' | 'fail';

/**
 * An individual issue or suggestion from analysis.
 */
export interface AnalysisFeedbackItem {
    type: string;
    message: string;
}

/**
 * Result from a single analyzer.
 */
export interface AnalyzerResult {
    score: number;
    status: AnalyzerStatus;
    recommendations: string[];
}

/**
 * Core analysis result data matching AnalysisResultDTO properties.
 */
export interface AnalysisResult {
    overall_score: number;
    readability_score: number;
    keyword_score: number;
    meta_score: number;
    content_score: number;
    issues: AnalysisFeedbackItem[];
    suggestions: AnalysisFeedbackItem[];
    passed_checks: string[];
    focus_keyword: string | null;
    word_count: number;
    analyzer_results: Partial<Record<AnalyzerName, AnalyzerResult>>;
}

/**
 * Analysis API response from AnalysisResultResource.
 *
 * Includes computed grade and count fields.
 */
export interface AnalysisResultResponse {
    overall_score: number;
    grade: AnalysisGrade;
    grade_label: string;
    grade_color: AnalysisGradeColor;
    scores: {
        readability: number;
        keyword: number;
        meta: number;
        content: number;
    };
    focus_keyword: string | null;
    word_count: number;
    issues: AnalysisFeedbackItem[];
    issue_count: number;
    suggestions: AnalysisFeedbackItem[];
    suggestion_count: number;
    passed_checks: string[];
    passed_count: number;
    analyzer_results: Partial<Record<AnalyzerName, AnalyzerResult>>;
}
