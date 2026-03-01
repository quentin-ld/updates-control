/**
 * Activity log panel — DataViews component for update logs.
 * Uses the official activity layout. Orchestrates view state, fields, actions,
 * and modal. Helpers and UI pieces live in sibling modules.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/
 */

import { useMemo, useState, useEffect, useCallback } from '@wordpress/element';
import { DataViews, filterSortAndPaginate } from '@wordpress/dataviews/wp';
import {
	Button,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useLogs } from '../../hooks/useLogs';
import { LAYOUT_ACTIVITY } from './constants';
import {
	statusToBadgeIntent,
	formatDate,
	getContextLabel,
	getActivityTitle,
	getActivityDescription,
	EMPTY_FALLBACK,
} from './utils';
import { StatusBadge } from './StatusBadge';
import { getIconForLogType } from './logTypeIcon';
import { LogDetailsContent } from './LogDetailsContent';

const FIXED_SORT = { field: 'date', direction: 'desc' };

const DELETE_MODAL_STYLE = {
	display: 'flex',
	gap: '8px',
	justifyContent: 'flex-end',
	marginTop: '16px',
};

/**
 * Activity log panel — renders inside a TabPanel.
 *
 * @param {Object}  props
 * @param {boolean} [props.loggingEnabled=true] Whether update logging is enabled.
 * @return {JSX.Element} The activity log panel UI.
 */
export function ActivityLogPanel({ loggingEnabled = true }) {
	const { logs, loading, error, fetchLogs, deleteLog } = useLogs();

	const [view, setView] = useState({
		type: LAYOUT_ACTIVITY,
		page: 1,
		perPage: 50,
		search: '',
		filters: [],
		sort: FIXED_SORT,
		fields: ['date', 'context', 'user', 'status'],
		titleField: 'title',
		descriptionField: 'description',
		mediaField: 'icon',
	});

	const handleChangeView = useCallback((nextView) => {
		setView(() => ({
			...nextView,
			sort: FIXED_SORT,
		}));
	}, []);

	useEffect(() => {
		fetchLogs({ per_page: view.perPage, page: view.page });
	}, [fetchLogs, view.perPage, view.page]);

	const fields = useMemo(
		() => [
			{
				id: 'title',
				label: __('Title', 'updatronix'),
				getValue: ({ item }) => getActivityTitle(item),
				enableSorting: false,
				enableGlobalSearch: true,
			},
			{
				id: 'description',
				label: __('Version', 'updatronix'),
				getValue: ({ item }) => getActivityDescription(item),
				enableSorting: false,
				enableGlobalSearch: true,
			},
			{
				id: 'icon',
				label: __('Type', 'updatronix'),
				getValue: ({ item }) => item.log_type || '',
				render: ({ item, config }) => {
					const size =
						config?.sizes &&
						typeof config.sizes === 'string' &&
						config.sizes.endsWith('px')
							? parseInt(config.sizes, 10)
							: 24;
					return getIconForLogType(item.log_type, size);
				},
				enableSorting: false,
				enableHiding: false,
				filterBy: false,
			},
			{
				id: 'date',
				label: __('Date', 'updatronix'),
				getValue: ({ item }) => item.created_at,
				render: ({ item }) => formatDate(item.created_at),
				enableSorting: false,
				sort: (a, b, direction) => {
					const tA = new Date(a.created_at).getTime();
					const tB = new Date(b.created_at).getTime();
					return direction === 'asc' ? tA - tB : tB - tA;
				},
				enableGlobalSearch: false,
			},
			{
				id: 'context',
				label: __('Context', 'updatronix'),
				getValue: ({ item }) => getContextLabel(item.update_context),
				enableSorting: false,
				enableGlobalSearch: true,
			},
			{
				id: 'user',
				label: __('User', 'updatronix'),
				getValue: ({ item }) => item.performed_by_display || '',
				render: ({ item }) =>
					item.user_edit_link ? (
						<a
							href={item.user_edit_link}
							rel="noopener noreferrer"
							target="_blank"
						>
							{item.performed_by_display || EMPTY_FALLBACK}
						</a>
					) : (
						<span>
							{item.performed_by_display || EMPTY_FALLBACK}
						</span>
					),
				enableSorting: false,
				enableGlobalSearch: true,
			},
			{
				id: 'status',
				label: __('Status', 'updatronix'),
				getValue: ({ item }) => item.status || '',
				render: ({ item }) => (
					<StatusBadge intent={statusToBadgeIntent(item.status)}>
						{item.status || EMPTY_FALLBACK}
					</StatusBadge>
				),
				enableSorting: false,
				enableGlobalSearch: true,
			},
		],
		[]
	);

	const actions = useMemo(
		() => [
			{
				id: 'view-logs',
				label: __('View log', 'updatronix'),
				modalHeader: __('Log details', 'updatronix'),
				modalSize: 'large',
				modalFocusOnMount: 'firstContentElement',
				isEligible: (item) => !!(item.message || item.trace),
				RenderModal: ({ items }) => (
					<LogDetailsContent log={items[0]} />
				),
			},
			{
				id: 'delete',
				label: __('Delete', 'updatronix'),
				modalHeader: __('Delete log', 'updatronix'),
				isDestructive: true,
				RenderModal: ({ items, closeModal, onActionPerformed }) => {
					const log = items[0];
					if (!log) {
						return null;
					}
					const handleConfirm = async () => {
						await deleteLog(log.id);
						onActionPerformed?.(items);
						closeModal?.();
					};
					return (
						<>
							<p>
								{__(
									'Are you sure you want to delete this log entry? This action cannot be undone.',
									'updatronix'
								)}
							</p>
							<div style={DELETE_MODAL_STYLE}>
								<Button variant="tertiary" onClick={closeModal}>
									{__('Cancel', 'updatronix')}
								</Button>
								<Button
									variant="primary"
									isDestructive
									onClick={handleConfirm}
								>
									{__('Confirm', 'updatronix')}
								</Button>
							</div>
						</>
					);
				},
			},
		],
		[deleteLog]
	);

	const defaultLayouts = useMemo(
		() => ({
			[LAYOUT_ACTIVITY]: {
				sort: FIXED_SORT,
				layout: { density: 'balanced' },
			},
		}),
		[]
	);

	const { data: shownData, paginationInfo } = useMemo(
		() => filterSortAndPaginate(logs, view, fields),
		[logs, view, fields]
	);

	if (error) {
		return (
			<div
				className="updatronix-logs-error notice notice-error is-dismissible"
				aria-live="assertive"
				role="alert"
			>
				<p>{error}</p>
			</div>
		);
	}

	if (!loggingEnabled) {
		return (
			<div className="updatronix-logs updatronix-activitylog-panel">
				<p className="updatronix-logs-disabled-message">
					{__(
						'Update logging is turned off. You can turn it on in the Settings tab.',
						'updatronix'
					)}
				</p>
				<div
					className="updatronix-logs-dataview-wrapper"
					style={{ display: 'none' }}
					aria-hidden="true"
				/>
			</div>
		);
	}

	return (
		<div className="updatronix-logs updatronix-activitylog-panel">
			<h2 className="updatronix-panel-title">
				{__('Update logs', 'updatronix')}
			</h2>
			<Text variant="muted">
				{__(
					'Browse and search the history of all updates on your site, both automatic and manual.',
					'updatronix'
				)}
			</Text>
			<DataViews
				getItemId={(item) => String(item.id)}
				view={view}
				onChangeView={handleChangeView}
				fields={fields}
				data={shownData}
				actions={actions}
				isLoading={loading}
				paginationInfo={paginationInfo}
				defaultLayouts={defaultLayouts}
				config={{ perPageSizes: [10, 25, 50, 100] }}
				empty={__(
					'No update logs yet. Logs will appear here after your first update.',
					'updatronix'
				)}
				search
				searchLabel={__('Search logs', 'updatronix')}
			/>
		</div>
	);
}
