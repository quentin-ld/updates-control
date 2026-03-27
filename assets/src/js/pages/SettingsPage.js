import {
	useState,
	useEffect,
	useCallback,
	lazy,
	Suspense,
} from '@wordpress/element';
import {
	backup as iconLogs,
	settings as iconSettings,
	update as iconUpdate,
} from '@wordpress/icons';
import { Notices } from '../components/Notices';
import { Tabs } from '../components/Tabs';
import { usePluginSettings } from '../hooks/usePluginSettings';
import { __ } from '@wordpress/i18n';

const TAB_LOGS = 'logs';
const TAB_AUTO_UPDATES = 'auto-updates';
const TAB_SETTINGS = 'settings';
const VALID_TABS = [TAB_LOGS, TAB_AUTO_UPDATES, TAB_SETTINGS];

const ActivityLogPanel = lazy(() =>
	import('../components/activityLog').then((module) => ({
		default: module.ActivityLogPanel,
	}))
);
const AutoUpdatesPanel = lazy(() =>
	import('../components/autoUpdates').then((module) => ({
		default: module.AutoUpdatesPanel,
	}))
);
const SettingsPanel = lazy(() =>
	import('../components/SettingsPanel').then((module) => ({
		default: module.SettingsPanel,
	}))
);

function getTabFromUrl() {
	const params = new URLSearchParams(window.location.search);
	const tab = params.get('tab');
	return VALID_TABS.includes(tab) ? tab : TAB_LOGS;
}

function setTabInUrl(tabId) {
	const params = new URLSearchParams(window.location.search);
	params.set('tab', tabId);
	const url = `${window.location.pathname}?${params.toString()}`;
	window.history.replaceState(null, '', url);
}

/**
 * Main settings page for Updatronix.
 * Tabs: Logs and Settings. Active tab is synced with ?tab= URL param for direct links.
 *
 * @return {JSX.Element} The settings page UI.
 */
export const SettingsPage = () => {
	const { settings, setSettings, saveSettings, saving } = usePluginSettings();
	const [selectedTabId, setSelectedTabId] = useState(getTabFromUrl);
	const [loadedTabs, setLoadedTabs] = useState(
		() => new Set([getTabFromUrl()])
	);

	const handleSelectTab = useCallback((tabId) => {
		setSelectedTabId(tabId);
		setTabInUrl(tabId);
	}, []);

	useEffect(() => {
		if (!selectedTabId || !VALID_TABS.includes(selectedTabId)) {
			setSelectedTabId(TAB_LOGS);
			setTabInUrl(TAB_LOGS);
		}
	}, [selectedTabId]);

	useEffect(() => {
		setLoadedTabs((previousTabs) => {
			if (previousTabs.has(selectedTabId)) {
				return previousTabs;
			}
			const nextTabs = new Set(previousTabs);
			nextTabs.add(selectedTabId);
			return nextTabs;
		});
	}, [selectedTabId]);

	const tabLoadingFallback = (
		<p aria-live="polite">{__('Loading section…', 'updatronix')}</p>
	);

	return (
		<main className="updatronix-row">
			<section className="updatronix-main">
				<div className="updatronix-notices">
					<Notices />
				</div>
				<div className="updatronix-panel">
					<Tabs
						orientation="vertical"
						selectedTabId={selectedTabId}
						onSelect={handleSelectTab}
					>
						<Tabs.TabList
							label={__(
								'Updatronix settings sections',
								'updatronix'
							)}
						>
							<Tabs.Tab
								tabId={TAB_LOGS}
								title={__('Update logs', 'updatronix')}
								icon={iconLogs}
							>
								{__('Update logs', 'updatronix')}
							</Tabs.Tab>
							<Tabs.Tab
								tabId={TAB_AUTO_UPDATES}
								title={__('Auto-updates', 'updatronix')}
								icon={iconUpdate}
							>
								{__('Auto-updates', 'updatronix')}
							</Tabs.Tab>
							<Tabs.Tab
								tabId={TAB_SETTINGS}
								title={__('Settings', 'updatronix')}
								icon={iconSettings}
							>
								{__('Settings', 'updatronix')}
							</Tabs.Tab>
						</Tabs.TabList>
						<Tabs.TabPanel tabId={TAB_LOGS}>
							{loadedTabs.has(TAB_LOGS) ? (
								<Suspense fallback={tabLoadingFallback}>
									<ActivityLogPanel
										loggingEnabled={
											settings.logging_enabled
										}
									/>
								</Suspense>
							) : null}
						</Tabs.TabPanel>
						<Tabs.TabPanel tabId={TAB_AUTO_UPDATES}>
							{loadedTabs.has(TAB_AUTO_UPDATES) ? (
								<Suspense fallback={tabLoadingFallback}>
									<AutoUpdatesPanel />
								</Suspense>
							) : null}
						</Tabs.TabPanel>
						<Tabs.TabPanel tabId={TAB_SETTINGS}>
							{loadedTabs.has(TAB_SETTINGS) ? (
								<Suspense fallback={tabLoadingFallback}>
									<SettingsPanel
										settings={settings}
										setSettings={setSettings}
										saveSettings={saveSettings}
										saving={saving}
									/>
								</Suspense>
							) : null}
						</Tabs.TabPanel>
					</Tabs>
				</div>
			</section>
		</main>
	);
};
