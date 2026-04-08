/**
 * RedirectManager component.
 *
 * CRUD table for URL redirects with filtering, sorting, pagination,
 * bulk actions, and a URL test tool.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.1.0
 */

import React, { useCallback, useMemo, useState } from 'react';

import {
	Alert,
	Badge,
	Button,
	Card,
	Checkbox,
	Input,
	Loading,
	Modal,
	Pagination,
	Select,
	Table,
	Textarea,
} from '@artisanpack-ui/react';

import type { UseApiOptions } from '../../hooks/useApi';
import { useRedirects } from '../../hooks/useRedirects';

import type { Redirect, RedirectMatchType, RedirectStatusCode } from '../../../types/redirect';
import type { RedirectSortField, SortDirection } from '../../../types/components';

/** Status code options for select inputs. */
const STATUS_CODE_OPTIONS = [
	{ value: '', label: 'All Status Codes' },
	{ value: '301', label: '301 - Permanent' },
	{ value: '302', label: '302 - Temporary' },
	{ value: '307', label: '307 - Temporary (Strict)' },
	{ value: '308', label: '308 - Permanent (Strict)' },
];

/** Match type options for select inputs. */
const MATCH_TYPE_OPTIONS = [
	{ value: '', label: 'All Match Types' },
	{ value: 'exact', label: 'Exact' },
	{ value: 'regex', label: 'Regex' },
	{ value: 'wildcard', label: 'Wildcard' },
];

/** Form status code options (without "all"). */
const FORM_STATUS_OPTIONS = STATUS_CODE_OPTIONS.slice( 1 );

/** Form match type options (without "all"). */
const FORM_MATCH_OPTIONS = MATCH_TYPE_OPTIONS.slice( 1 );

/** Props for the RedirectManager component. */
export interface RedirectManagerProps extends UseApiOptions {
	/** Additional CSS class name. */
	className?: string;
}

/** Redirect form data shape. */
interface RedirectFormData {
	from_path: string;
	to_path: string;
	status_code: string;
	match_type: string;
	is_active: boolean;
	notes: string;
}

const EMPTY_FORM: RedirectFormData = {
	from_path: '',
	to_path: '',
	status_code: '301',
	match_type: 'exact',
	is_active: true,
	notes: '',
};

/**
 * URL redirect management interface.
 *
 * Provides a full CRUD table with search, filtering by status code
 * and match type, sortable columns, pagination, bulk delete/status
 * change, and a URL test tool.
 *
 * @example
 * ```tsx
 * <RedirectManager baseUrl="/api/seo" />
 * ```
 */
export function RedirectManager( { className, ...apiOptions }: RedirectManagerProps ): React.ReactElement {
	const {
		redirects,
		meta,
		loading,
		mutating,
		error,
		validationErrors,
		filters,
		sort,
		page,
		fetchRedirects,
		createRedirect,
		updateRedirect,
		deleteRedirect,
		bulkAction,
		testUrl,
		setFilters,
		setSort,
		setPage,
	} = useRedirects( apiOptions );

	const [showCreateModal, setShowCreateModal] = useState( false );
	const [editingRedirect, setEditingRedirect] = useState<Redirect | null>( null );
	const [formData, setFormData] = useState<RedirectFormData>( EMPTY_FORM );
	const [selectedIds, setSelectedIds] = useState<Set<number>>( new Set() );
	const [testUrlValue, setTestUrlValue] = useState( '' );
	const [testResult, setTestResult] = useState<string | null>( null );
	const [searchValue, setSearchValue] = useState( '' );
	const [searchTimer, setSearchTimer] = useState<ReturnType<typeof setTimeout> | null>( null );

	// Debounced search
	const handleSearchChange = useCallback( ( value: string ): void => {
		setSearchValue( value );

		if ( searchTimer ) {
			clearTimeout( searchTimer );
		}

		const timer = setTimeout( () => {
			setFilters( { ...filters, search: value || undefined } );
		}, 300 );
		setSearchTimer( timer );
	}, [filters, setFilters, searchTimer] );

	// Form handlers
	const openCreate = useCallback( (): void => {
		setFormData( EMPTY_FORM );
		setShowCreateModal( true );
	}, [] );

	const openEdit = useCallback( ( redirect: Redirect ): void => {
		setFormData( {
			from_path: redirect.from_path,
			to_path: redirect.to_path,
			status_code: String( redirect.status_code ),
			match_type: redirect.match_type,
			is_active: redirect.is_active,
			notes: redirect.notes ?? '',
		} );
		setEditingRedirect( redirect );
	}, [] );

	const closeModals = useCallback( (): void => {
		setShowCreateModal( false );
		setEditingRedirect( null );
		setFormData( EMPTY_FORM );
	}, [] );

	const handleFormChange = useCallback( ( field: keyof RedirectFormData, value: unknown ): void => {
		setFormData( ( prev ) => ( { ...prev, [field]: value } ) );
	}, [] );

	const handleCreate = useCallback( async (): Promise<void> => {
		const result = await createRedirect( {
			...formData,
			status_code: parseInt( formData.status_code, 10 ),
		} );

		if ( result ) {
			closeModals();
		}
	}, [createRedirect, formData, closeModals] );

	const handleUpdate = useCallback( async (): Promise<void> => {
		if ( !editingRedirect ) {
			return;
		}

		const result = await updateRedirect( editingRedirect.id, {
			...formData,
			status_code: parseInt( formData.status_code, 10 ),
		} );

		if ( result ) {
			closeModals();
		}
	}, [updateRedirect, editingRedirect, formData, closeModals] );

	const handleDelete = useCallback( async ( id: number ): Promise<void> => {
		if ( window.confirm( 'Are you sure you want to delete this redirect?' ) ) {
			await deleteRedirect( id );
		}
	}, [deleteRedirect] );

	// Bulk actions
	const handleBulkDelete = useCallback( async (): Promise<void> => {
		if ( 0 === selectedIds.size ) {
			return;
		}

		if ( window.confirm( `Delete ${ selectedIds.size } selected redirect(s)?` ) ) {
			const success = await bulkAction( 'delete', Array.from( selectedIds ) );

			if ( success ) {
				setSelectedIds( new Set() );
			}
		}
	}, [bulkAction, selectedIds] );

	const toggleSelectAll = useCallback( (): void => {
		if ( selectedIds.size === redirects.length ) {
			setSelectedIds( new Set() );
		} else {
			setSelectedIds( new Set( redirects.map( ( r ) => r.id ) ) );
		}
	}, [selectedIds, redirects] );

	const toggleSelect = useCallback( ( id: number ): void => {
		setSelectedIds( ( prev ) => {
			const next = new Set( prev );

			if ( next.has( id ) ) {
				next.delete( id );
			} else {
				next.add( id );
			}

			return next;
		} );
	}, [] );

	// URL test
	const handleTestUrl = useCallback( async (): Promise<void> => {
		if ( !testUrlValue.trim() ) {
			return;
		}

		const result = await testUrl( testUrlValue );

		if ( result?.matched ) {
			setTestResult( `Matches redirect #${ result.matched.id }: ${ result.matched.from_path } -> ${ result.resolved_destination }` );
		} else {
			setTestResult( 'No matching redirect found.' );
		}
	}, [testUrl, testUrlValue] );

	// Sorting
	const handleSort = useCallback( ( field: RedirectSortField ): void => {
		const direction: SortDirection = sort.field === field && 'asc' === sort.direction ? 'desc' : 'asc';
		setSort( { field, direction } );
	}, [sort, setSort] );

	const sortIndicator = useCallback( ( field: RedirectSortField ): string => {
		if ( sort.field !== field ) {
			return '';
		}

		return 'asc' === sort.direction ? ' \u2191' : ' \u2193';
	}, [sort] );

	// Table headers
	const headers = useMemo( () => [
		{ key: 'select', label: '', className: 'w-10' },
		{
			key: 'from_path',
			label: `From${ sortIndicator( 'from_path' ) }`,
			sortable: true,
		},
		{
			key: 'to_path',
			label: `To${ sortIndicator( 'to_path' ) }`,
			sortable: true,
		},
		{
			key: 'status_code',
			label: `Status${ sortIndicator( 'status_code' ) }`,
			sortable: true,
			className: 'w-24',
		},
		{ key: 'match_type', label: 'Type', className: 'w-24' },
		{
			key: 'hits',
			label: `Hits${ sortIndicator( 'hits' ) }`,
			sortable: true,
			className: 'w-20',
		},
		{ key: 'actions', label: '', className: 'w-28' },
	], [sortIndicator] );

	// Redirect form modal content
	const formModal = (
		<div className="flex flex-col gap-4">
			<Input
				label="From Path"
				value={ formData.from_path }
				onChange={ ( e ) => handleFormChange( 'from_path', e.target.value ) }
				hint="The URL path to redirect from (e.g. /old-page)"
				error={ validationErrors.from_path?.[0] }
				required
			/>

			<Input
				label="To Path"
				value={ formData.to_path }
				onChange={ ( e ) => handleFormChange( 'to_path', e.target.value ) }
				hint="The destination URL or path (e.g. /new-page or https://example.com)"
				error={ validationErrors.to_path?.[0] }
				required
			/>

			<div className="grid grid-cols-2 gap-4">
				<Select
					label="Status Code"
					value={ formData.status_code }
					onChange={ ( e ) => handleFormChange( 'status_code', e.target.value ) }
					options={ FORM_STATUS_OPTIONS }
					optionValue="value"
					optionLabel="label"
					error={ validationErrors.status_code?.[0] }
				/>

				<Select
					label="Match Type"
					value={ formData.match_type }
					onChange={ ( e ) => handleFormChange( 'match_type', e.target.value ) }
					options={ FORM_MATCH_OPTIONS }
					optionValue="value"
					optionLabel="label"
					error={ validationErrors.match_type?.[0] }
				/>
			</div>

			<Checkbox
				label="Active"
				checked={ formData.is_active }
				onChange={ ( e ) => handleFormChange( 'is_active', e.target.checked ) }
			/>

			<Textarea
				label="Notes"
				value={ formData.notes }
				onChange={ ( e ) => handleFormChange( 'notes', e.target.value ) }
				rows={ 2 }
				error={ validationErrors.notes?.[0] }
			/>
		</div>
	);

	return (
		<div className={ className }>
			{ error && (
				<Alert color="error" className="mb-4">
					{ error }
				</Alert>
			) }

			{/* Toolbar */}
			<div className="flex flex-wrap items-center gap-2 mb-4">
				<Input
					value={ searchValue }
					onChange={ ( e ) => handleSearchChange( e.target.value ) }
					placeholder="Search redirects..."
					className="w-64"
				/>

				<Select
					value={ filters.status_code ? String( filters.status_code ) : '' }
					onChange={ ( e ) => setFilters( {
						...filters,
						status_code: e.target.value ? parseInt( e.target.value, 10 ) as RedirectStatusCode : undefined,
					} ) }
					options={ STATUS_CODE_OPTIONS }
					optionValue="value"
					optionLabel="label"
					className="w-48"
				/>

				<Select
					value={ filters.match_type ?? '' }
					onChange={ ( e ) => setFilters( {
						...filters,
						match_type: ( e.target.value || undefined ) as RedirectMatchType | undefined,
					} ) }
					options={ MATCH_TYPE_OPTIONS }
					optionValue="value"
					optionLabel="label"
					className="w-44"
				/>

				<div className="ml-auto flex gap-2">
					{ selectedIds.size > 0 && (
						<Button
							color="error"
							size="sm"
							onClick={ handleBulkDelete }
							disabled={ mutating }
						>
							Delete Selected ({ selectedIds.size })
						</Button>
					) }

					<Button
						color="primary"
						size="sm"
						onClick={ openCreate }
					>
						Add Redirect
					</Button>
				</div>
			</div>

			{/* URL Test Tool */}
			<Card className="mb-4">
				<div className="p-3 flex gap-2 items-end">
					<Input
						label="Test URL"
						value={ testUrlValue }
						onChange={ ( e ) => setTestUrlValue( e.target.value ) }
						placeholder="Enter a URL path to test..."
						className="flex-1"
					/>
					<Button
						size="sm"
						onClick={ handleTestUrl }
					>
						Test
					</Button>
				</div>
				{ testResult && (
					<div className="px-3 pb-3">
						<Alert color="info">{ testResult }</Alert>
					</div>
				) }
			</Card>

			{/* Table */}
			{ loading ? (
				<Loading />
			) : (
				<>
					<div className="overflow-x-auto">
						<table className="table table-zebra w-full">
							<thead>
								<tr>
									<th className="w-10">
										<Checkbox
											checked={ redirects.length > 0 && selectedIds.size === redirects.length }
											onChange={ toggleSelectAll }
											aria-label="Select all"
										/>
									</th>
									<th
										className="cursor-pointer select-none"
										onClick={ () => handleSort( 'from_path' ) }
										aria-sort={ sort.field === 'from_path' ? ( 'asc' === sort.direction ? 'ascending' : 'descending' ) : undefined }
									>
										From{ sortIndicator( 'from_path' ) }
									</th>
									<th
										className="cursor-pointer select-none"
										onClick={ () => handleSort( 'to_path' ) }
										aria-sort={ sort.field === 'to_path' ? ( 'asc' === sort.direction ? 'ascending' : 'descending' ) : undefined }
									>
										To{ sortIndicator( 'to_path' ) }
									</th>
									<th
										className="cursor-pointer select-none w-24"
										onClick={ () => handleSort( 'status_code' ) }
									>
										Status{ sortIndicator( 'status_code' ) }
									</th>
									<th className="w-24">Type</th>
									<th
										className="cursor-pointer select-none w-20"
										onClick={ () => handleSort( 'hits' ) }
									>
										Hits{ sortIndicator( 'hits' ) }
									</th>
									<th className="w-28"></th>
								</tr>
							</thead>
							<tbody>
								{ 0 === redirects.length && (
									<tr>
										<td colSpan={ 7 } className="text-center text-base-content/50 py-8">
											No redirects found.
										</td>
									</tr>
								) }
								{ redirects.map( ( redirect ) => (
									<tr key={ redirect.id }>
										<td>
											<Checkbox
												checked={ selectedIds.has( redirect.id ) }
												onChange={ () => toggleSelect( redirect.id ) }
												aria-label={ `Select redirect ${ redirect.id }` }
											/>
										</td>
										<td className="font-mono text-sm break-all">
											{ redirect.from_path }
											{ !redirect.is_active && (
												<Badge color="warning" size="xs" className="ml-1">Inactive</Badge>
											) }
										</td>
										<td className="font-mono text-sm break-all">
											{ redirect.to_path }
										</td>
										<td>
											<Badge
												color={ redirect.is_permanent ? 'info' : 'warning' }
												size="sm"
											>
												{ redirect.status_code }
											</Badge>
										</td>
										<td className="text-sm">{ redirect.match_type_label }</td>
										<td className="text-sm">{ redirect.hits }</td>
										<td>
											<div className="flex gap-1">
												<Button
													size="xs"
													onClick={ () => openEdit( redirect ) }
													aria-label="Edit"
												>
													Edit
												</Button>
												<Button
													size="xs"
													color="error"
													onClick={ () => handleDelete( redirect.id ) }
													disabled={ mutating }
													aria-label="Delete"
												>
													Delete
												</Button>
											</div>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</div>

					{ meta && meta.last_page > 1 && (
						<div className="mt-4 flex justify-center">
							<Pagination
								totalPages={ meta.last_page }
								currentPage={ page }
								onChange={ setPage }
							/>
						</div>
					) }
				</>
			) }

			{/* Create Modal */}
			<Modal
				open={ showCreateModal }
				onClose={ closeModals }
				title="Create Redirect"
			>
				{ formModal }
				<div slot="actions" className="flex gap-2 mt-4 justify-end">
					<Button onClick={ closeModals }>Cancel</Button>
					<Button
						color="primary"
						onClick={ handleCreate }
						disabled={ mutating }
					>
						{ mutating ? 'Creating...' : 'Create' }
					</Button>
				</div>
			</Modal>

			{/* Edit Modal */}
			<Modal
				open={ null !== editingRedirect }
				onClose={ closeModals }
				title="Edit Redirect"
			>
				{ formModal }
				<div slot="actions" className="flex gap-2 mt-4 justify-end">
					<Button onClick={ closeModals }>Cancel</Button>
					<Button
						color="primary"
						onClick={ handleUpdate }
						disabled={ mutating }
					>
						{ mutating ? 'Saving...' : 'Save Changes' }
					</Button>
				</div>
			</Modal>
		</div>
	);
}
