<!--
  SeoDashboard component.

  Overview dashboard displaying quick SEO stats, recent analysis
  scores, and redirect statistics.

  @package    ArtisanPack_UI
  @subpackage SEO

  @since      1.1.0
-->

<script setup lang="ts">
import { onMounted, ref } from 'vue';

import { Alert, Badge, Card, Loading, Stat } from '@artisanpack-ui/vue';

import type { UseApiOptions } from '../../composables/useApi';
import { useApi } from '../../composables/useApi';

import type { Redirect } from '../../../types/redirect';

interface DashboardStats {
	totalRedirects: number;
	activeRedirects: number;
	totalHits: number;
	recentRedirects: Redirect[];
}

const props = withDefaults( defineProps<UseApiOptions & {
	modelType?: string;
	modelId?: number;
	className?: string;
}>(), {
	csrfToken: undefined,
	authorization: undefined,
	credentials: undefined,
	modelType: undefined,
	modelId: undefined,
	className: undefined,
} );

const api = useApi( {
	baseUrl: props.baseUrl,
	csrfToken: props.csrfToken,
	authorization: props.authorization,
	credentials: props.credentials,
} );

const encodedModelType = props.modelType ? encodeURIComponent( props.modelType ) : null;

const stats          = ref<DashboardStats | null>( null );
const analysisScore  = ref<number | null>( null );
const analysisGrade  = ref<string | null>( null );
const isLoading      = ref( true );
const error          = ref<string | null>( null );

async function fetchDashboardData(): Promise<void> {
	isLoading.value = true;
	error.value     = null;

	try {
		const recentResponse = await api.get<{
			data: Redirect[];
			meta: { total: number };
		}>( '/redirects', { per_page: '5', sort_by: 'created_at', sort_order: 'desc' } );

		const activeResponse = await api.get<{
			meta: { total: number };
		}>( '/redirects', { per_page: '1', is_active: '1' } );

		const totalRedirects  = recentResponse.meta?.total ?? recentResponse.data.length;
		const activeRedirects = activeResponse.meta?.total ?? 0;
		const recentRedirects = recentResponse.data;
		const recentHits      = recentRedirects.reduce( ( sum, r ) => sum + r.hits, 0 );

		stats.value = {
			totalRedirects,
			activeRedirects,
			totalHits: recentHits,
			recentRedirects,
		};

		// Reset analysis state
		analysisScore.value = null;
		analysisGrade.value = null;

		if ( encodedModelType && props.modelId ) {
			try {
				const analysisResponse = await api.get<{
					data: { overall_score: number; grade_label: string };
				}>( `/analysis/${ encodedModelType }/${ props.modelId }` );

				analysisScore.value = analysisResponse.data.overall_score;
				analysisGrade.value = analysisResponse.data.grade_label;
			} catch {
				analysisScore.value = null;
				analysisGrade.value = null;
			}
		}
	} catch ( err ) {
		error.value = err instanceof Error ? err.message : 'Failed to load dashboard data.';
	} finally {
		isLoading.value = false;
	}
}

onMounted( fetchDashboardData );
</script>

<template>
	<div :class="className">
		<Loading v-if="isLoading" />

		<Alert v-else-if="error" color="error">{{ error }}</Alert>

		<template v-else>
			<!-- Stats Row -->
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
				<Stat title="Total Redirects" :value="String( stats?.totalRedirects ?? 0 )" />
				<Stat title="Active Redirects" :value="String( stats?.activeRedirects ?? 0 )" />
				<Stat title="Recent Hits" :value="String( stats?.totalHits ?? 0 )" description="From latest 5 redirects" />
				<Stat v-if="analysisScore !== null" title="SEO Score" :value="String( analysisScore )" :description="analysisGrade ?? undefined" />
			</div>

			<!-- Recent Redirects -->
			<Card>
				<div class="p-4">
					<h3 class="font-semibold text-lg mb-3">Recent Redirects</h3>

					<p v-if="( stats?.recentRedirects.length ?? 0 ) === 0" class="text-base-content/60 text-sm">
						No redirects yet.
					</p>

					<div v-else class="overflow-x-auto">
						<table class="table table-sm w-full">
							<thead>
								<tr>
									<th>From</th>
									<th>To</th>
									<th>Status</th>
									<th>Hits</th>
								</tr>
							</thead>
							<tbody>
								<tr v-for="redirect in stats?.recentRedirects" :key="redirect.id">
									<td class="font-mono text-xs">{{ redirect.from_path }}</td>
									<td class="font-mono text-xs">{{ redirect.to_path }}</td>
									<td>
										<Badge :color="redirect.is_permanent ? 'info' : 'warning'" size="xs">
											{{ redirect.status_code }}
										</Badge>
									</td>
									<td>{{ redirect.hits }}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</Card>
		</template>
	</div>
</template>
