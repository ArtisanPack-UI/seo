/**
 * SeoDashboard component.
 *
 * Overview dashboard displaying quick SEO stats, recent analysis
 * scores, and redirect statistics.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React, { useCallback, useEffect, useMemo, useState } from 'react';

import { Alert, Badge, Card, Loading, Stat } from '@artisanpack-ui/react';

import type { UseApiOptions } from '../../hooks/useApi';
import { useApi } from '../../hooks/useApi';

import type { Redirect } from '../../../types/redirect';

/** Dashboard statistics shape. */
interface DashboardStats {
	totalRedirects: number;
	activeRedirects: number;
	totalHits: number;
	recentRedirects: Redirect[];
}

/** Props for the SeoDashboard component. */
export interface SeoDashboardProps extends UseApiOptions {
	/** The model type for analysis display (optional). */
	modelType?: string;
	/** The model ID for analysis display (optional). */
	modelId?: number;
	/** Additional CSS class name. */
	className?: string;
}

/**
 * SEO overview dashboard.
 *
 * Displays redirect statistics (total, active, total hits) and
 * a list of recently created redirects. When modelType/modelId
 * are provided, also shows the latest analysis score for that model.
 *
 * @example
 * ```tsx
 * <SeoDashboard baseUrl="/api/seo" />
 * ```
 */
export function SeoDashboard( {
	modelType,
	modelId,
	className,
	...apiOptions
}: SeoDashboardProps ): React.ReactElement {
	const api = useApi( apiOptions );
	const encodedModelType = useMemo(
		() => modelType ? encodeURIComponent( modelType ) : null,
		[modelType],
	);

	const [stats, setStats] = useState<DashboardStats | null>( null );
	const [analysisScore, setAnalysisScore] = useState<number | null>( null );
	const [analysisGrade, setAnalysisGrade] = useState<string | null>( null );
	const [loading, setLoading] = useState( true );
	const [error, setError] = useState<string | null>( null );

	const fetchDashboardData = useCallback( async (): Promise<void> => {
		setLoading( true );
		setError( null );

		try {
			// Fetch recent redirects for display
			const recentResponse = await api.get<{
				data: Redirect[];
				meta: { total: number };
			}>( '/redirects', { per_page: '5', sort_by: 'created_at', sort_direction: 'desc' } );

			// Fetch active redirect count separately
			const activeResponse = await api.get<{
				meta: { total: number };
			}>( '/redirects', { per_page: '1', is_active: '1' } );

			const totalRedirects  = recentResponse.meta?.total ?? recentResponse.data.length;
			const activeRedirects = activeResponse.meta?.total ?? 0;
			const recentRedirects = recentResponse.data;
			const recentHits      = recentRedirects.reduce( ( sum, r ) => sum + r.hits, 0 );

			setStats( {
				totalRedirects,
				activeRedirects,
				totalHits: recentHits,
				recentRedirects,
			} );

			// Fetch analysis if model provided
			if ( encodedModelType && modelId ) {
				try {
					const analysisResponse = await api.get<{
						data: { overall_score: number; grade_label: string };
					}>( `/analysis/${ encodedModelType }/${ modelId }` );

					setAnalysisScore( analysisResponse.data.overall_score );
					setAnalysisGrade( analysisResponse.data.grade_label );
				} catch {
					// No analysis data is not an error
				}
			}
		} catch ( err ) {
			setError( err instanceof Error ? err.message : 'Failed to load dashboard data.' );
		} finally {
			setLoading( false );
		}
	}, [api, encodedModelType, modelId] );

	useEffect( () => {
		fetchDashboardData();
	}, [fetchDashboardData] );

	if ( loading ) {
		return <Loading />;
	}

	if ( error ) {
		return <Alert color="error">{ error }</Alert>;
	}

	return (
		<div className={ className }>
			{/* Stats Row */}
			<div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
				<Stat
					title="Total Redirects"
					value={ String( stats?.totalRedirects ?? 0 ) }
				/>

				<Stat
					title="Active Redirects"
					value={ String( stats?.activeRedirects ?? 0 ) }
				/>

				<Stat
					title="Recent Hits"
					value={ String( stats?.totalHits ?? 0 ) }
					description="From latest 5 redirects"
				/>

				{ null !== analysisScore && (
					<Stat
						title="SEO Score"
						value={ String( analysisScore ) }
						description={ analysisGrade ?? undefined }
					/>
				) }
			</div>

			{/* Recent Redirects */}
			<Card>
				<div className="p-4">
					<h3 className="font-semibold text-lg mb-3">Recent Redirects</h3>

					{ 0 === ( stats?.recentRedirects.length ?? 0 ) ? (
						<p className="text-base-content/60 text-sm">No redirects yet.</p>
					) : (
						<div className="overflow-x-auto">
							<table className="table table-sm w-full">
								<thead>
									<tr>
										<th>From</th>
										<th>To</th>
										<th>Status</th>
										<th>Hits</th>
									</tr>
								</thead>
								<tbody>
									{ stats?.recentRedirects.map( ( redirect ) => (
										<tr key={ redirect.id }>
											<td className="font-mono text-xs">{ redirect.from_path }</td>
											<td className="font-mono text-xs">{ redirect.to_path }</td>
											<td>
												<Badge
													color={ redirect.is_permanent ? 'info' : 'warning' }
													size="xs"
												>
													{ redirect.status_code }
												</Badge>
											</td>
											<td>{ redirect.hits }</td>
										</tr>
									) ) }
								</tbody>
							</table>
						</div>
					) }
				</div>
			</Card>
		</div>
	);
}
