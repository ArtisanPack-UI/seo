<?php

/**
 * AnalysisResultResource.
 *
 * API Resource for serializing AnalysisResultDTO data.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Http\Resources;

use ArtisanPackUI\SEO\DTOs\AnalysisResultDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * AnalysisResultResource class.
 *
 * Transforms AnalysisResultDTO data for API responses with
 * per-analyzer scores, recommendations, and overall scoring.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class AnalysisResultResource extends JsonResource
{
	/**
	 * Create a new resource instance from an AnalysisResultDTO.
	 *
	 * @since 1.1.0
	 *
	 * @param  AnalysisResultDTO  $resource  The DTO to serialize.
	 */
	public function __construct( AnalysisResultDTO $resource )
	{
		parent::__construct( $resource );
	}

	/**
	 * Transform the resource into an array.
	 *
	 * @since 1.1.0
	 *
	 * @param  Request  $request  The incoming request.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray( Request $request ): array
	{
		return [
			'overall_score'     => $this->resource->overallScore,
			'grade'             => $this->resource->getGrade(),
			'grade_label'       => $this->resource->getGradeLabel(),
			'grade_color'       => $this->resource->getGradeColor(),
			'scores'            => [
				'readability' => $this->resource->readabilityScore,
				'keyword'     => $this->resource->keywordScore,
				'meta'        => $this->resource->metaScore,
				'content'     => $this->resource->contentScore,
			],
			'focus_keyword'     => $this->resource->focusKeyword,
			'word_count'        => $this->resource->wordCount,
			'issues'            => $this->resource->issues,
			'issue_count'       => $this->resource->getIssueCount(),
			'suggestions'       => $this->resource->suggestions,
			'suggestion_count'  => $this->resource->getSuggestionCount(),
			'passed_checks'     => $this->resource->passedChecks,
			'passed_count'      => $this->resource->getPassedCount(),
			'analyzer_results'  => $this->resource->analyzerResults,
		];
	}
}
