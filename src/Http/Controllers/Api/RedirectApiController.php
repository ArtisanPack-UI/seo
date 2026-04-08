<?php

/**
 * RedirectApiController.
 *
 * API controller for managing URL redirects.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.1.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\SEO\Http\Controllers\Api;

use ArtisanPackUI\SEO\Http\Requests\Api\BulkRedirectRequest;
use ArtisanPackUI\SEO\Http\Requests\Api\StoreRedirectRequest;
use ArtisanPackUI\SEO\Http\Requests\Api\TestRedirectRequest;
use ArtisanPackUI\SEO\Http\Requests\Api\UpdateRedirectRequest;
use ArtisanPackUI\SEO\Http\Resources\RedirectResource;
use ArtisanPackUI\SEO\Models\Redirect;
use ArtisanPackUI\SEO\Services\RedirectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * RedirectApiController class.
 *
 * Handles API requests for redirect CRUD, bulk actions, and testing.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */
class RedirectApiController extends Controller
{
	/**
	 * Create a new RedirectApiController instance.
	 *
	 * @since 1.1.0
	 *
	 * @param  RedirectService  $redirectService  The redirect service.
	 */
	public function __construct(
		protected RedirectService $redirectService,
	) {
	}

	/**
	 * List redirects with pagination and filtering.
	 *
	 * @since 1.1.0
	 *
	 * @param  Request  $request  The incoming request.
	 *
	 * @return AnonymousResourceCollection
	 */
	public function index( Request $request ): AnonymousResourceCollection
	{
		$query = Redirect::query();

		// Filter by status code
		if ( $request->has( 'status_code' ) ) {
			$query->withStatusCode( (int) $request->input( 'status_code' ) );
		}

		// Filter by match type
		if ( $request->has( 'match_type' ) ) {
			$query->ofType( $request->input( 'match_type' ) );
		}

		// Filter by active status
		if ( $request->has( 'is_active' ) ) {
			if ( $request->boolean( 'is_active' ) ) {
				$query->active();
			} else {
				$query->inactive();
			}
		}

		// Search by from_path or to_path
		if ( $request->filled( 'search' ) ) {
			$search = $request->input( 'search' );
			$query->where( function ( $q ) use ( $search ): void {
				$q->where( 'from_path', 'like', '%' . $search . '%' )
					->orWhere( 'to_path', 'like', '%' . $search . '%' );
			} );
		}

		// Sort (validate against allowlist to prevent injection)
		$allowedSortColumns = [ 'id', 'from_path', 'to_path', 'status_code', 'match_type', 'hits', 'last_hit_at', 'created_at', 'updated_at' ];
		$sortBy             = in_array( $request->input( 'sort_by' ), $allowedSortColumns, true )
			? $request->input( 'sort_by' )
			: 'created_at';
		$sortOrder = in_array( $request->input( 'sort_order' ), [ 'asc', 'desc' ], true )
			? $request->input( 'sort_order' )
			: 'desc';
		$query->orderBy( $sortBy, $sortOrder );

		$perPage = max( min( (int) $request->input( 'per_page', 15 ), 100 ), 1 );

		return RedirectResource::collection( $query->paginate( $perPage ) );
	}

	/**
	 * Create a new redirect.
	 *
	 * @since 1.1.0
	 *
	 * @param  StoreRedirectRequest  $request  The validated request.
	 *
	 * @return JsonResponse
	 */
	public function store( StoreRedirectRequest $request ): JsonResponse
	{
		try {
			$redirect = $this->redirectService->create( $request->validated() );

			return ( new RedirectResource( $redirect ) )
				->response()
				->setStatusCode( 201 );
		} catch ( InvalidArgumentException $e ) {
			return response()->json( [
				'message' => $e->getMessage(),
			], 422 );
		}
	}

	/**
	 * Get redirect detail with hit statistics.
	 *
	 * @since 1.1.0
	 *
	 * @param  Redirect  $redirect  The redirect model.
	 *
	 * @return RedirectResource
	 */
	public function show( Redirect $redirect ): RedirectResource
	{
		return new RedirectResource( $redirect );
	}

	/**
	 * Update a redirect.
	 *
	 * @since 1.1.0
	 *
	 * @param  UpdateRedirectRequest  $request   The validated request.
	 * @param  Redirect               $redirect  The redirect model.
	 *
	 * @return JsonResponse|RedirectResource
	 */
	public function update( UpdateRedirectRequest $request, Redirect $redirect ): JsonResponse|RedirectResource
	{
		try {
			$redirect = $this->redirectService->update( $redirect, $request->validated() );

			return new RedirectResource( $redirect );
		} catch ( InvalidArgumentException $e ) {
			return response()->json( [
				'message' => $e->getMessage(),
			], 422 );
		}
	}

	/**
	 * Delete a redirect.
	 *
	 * @since 1.1.0
	 *
	 * @param  Redirect  $redirect  The redirect model.
	 *
	 * @return JsonResponse
	 */
	public function destroy( Redirect $redirect ): JsonResponse
	{
		$this->redirectService->delete( $redirect );

		return response()->json( null, 204 );
	}

	/**
	 * Perform bulk actions on redirects.
	 *
	 * @since 1.1.0
	 *
	 * @param  BulkRedirectRequest  $request  The validated request.
	 *
	 * @return JsonResponse
	 */
	public function bulk( BulkRedirectRequest $request ): JsonResponse
	{
		$action = $request->validated( 'action' );
		$ids    = $request->validated( 'ids' );

		$affected = 0;

		if ( 'delete' === $action ) {
			DB::transaction( function () use ( $ids, &$affected ): void {
				$redirects = Redirect::whereIn( 'id', $ids )->get();
				foreach ( $redirects as $redirect ) {
					$this->redirectService->delete( $redirect );
				}
				$affected = $redirects->count();
			} );
		} elseif ( 'change_status_code' === $action ) {
			$statusCode = (int) $request->validated( 'status_code' );
			$affected   = $this->redirectService->bulkUpdateStatusCode( $ids, $statusCode );
		}

		$message = match ( $action ) {
			'delete' => __( ':count redirects deleted.', [ 'count' => $affected ] ),
			default  => __( ':count redirects updated.', [ 'count' => $affected ] ),
		};

		return response()->json( [
			'message'  => $message,
			'affected' => $affected,
		] );
	}

	/**
	 * Test a URL against redirect rules.
	 *
	 * @since 1.1.0
	 *
	 * @param  TestRedirectRequest  $request  The validated request.
	 *
	 * @return JsonResponse
	 */
	public function test( TestRedirectRequest $request ): JsonResponse
	{
		$url      = $request->validated( 'url' );
		$redirect = $this->redirectService->findMatch( $url );

		if ( null === $redirect ) {
			return response()->json( [
				'data' => null,
			] );
		}

		return response()->json( [
			'data' => [
				'redirect'    => new RedirectResource( $redirect ),
				'destination' => $redirect->getResolvedDestination( $url ),
			],
		] );
	}
}
