import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	backup as iconLogs,
	calendar as iconSchedule,
	settings as iconSettings,
	update as iconUpdate,
} from '@wordpress/icons';
import { Notices } from '../components/Notices';
import { Tabs } from '../components/Tabs';
import { ActivityLogPanel } from '../components/activityLog';
import { AutoUpdatesPanel } from '../components/autoUpdates';
import { SchedulePanel } from '../components/schedule';
import { SettingsPanel } from '../components/SettingsPanel';
import { usePluginSettings } from '../hooks/usePluginSettings';
import { __ } from '@wordpress/i18n';

const TAB_LOGS = 'logs';
const TAB_AUTO_UPDATES = 'auto-updates';
const TAB_SCHEDULE = 'schedule';
const TAB_SETTINGS = 'settings';
const VALID_TABS = [TAB_LOGS, TAB_AUTO_UPDATES, TAB_SCHEDULE, TAB_SETTINGS];

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
 * Render the main settings page for Updatronix.
 *
 * Active tab is synced with the ?tab= URL parameter for direct linking.
 *
 * @return {JSX.Element} The settings page UI.
 */
export const SettingsPage = () => {
	const {
		settings,
		setSettings,
		saveSettings,
		saving,
		scheduleMeta,
		wpConfigConstants,
		dismissConstantNotice,
	} = usePluginSettings();

	const syncDismissedConstants = useCallback(
		(dismissed) => {
			setSettings((prev) => ({
				...prev,
				dismissed_constants: dismissed,
			}));
		},
		[setSettings]
	);
	const [selectedTabId, setSelectedTabId] = useState(getTabFromUrl);

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

	return (
		<div className="updatronix-row">
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
								tabId={TAB_SCHEDULE}
								title={__('Schedule', 'updatronix')}
								icon={iconSchedule}
							>
								{__('Schedule', 'updatronix')}
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
							<ActivityLogPanel
								loggingEnabled={settings.logging_enabled}
							/>
						</Tabs.TabPanel>
						<Tabs.TabPanel tabId={TAB_AUTO_UPDATES}>
							<AutoUpdatesPanel
								dismissedConstants={
									settings.dismissed_constants
								}
								onDismissedConstantsChange={
									syncDismissedConstants
								}
							/>
						</Tabs.TabPanel>
						<Tabs.TabPanel tabId={TAB_SCHEDULE}>
							<SchedulePanel
								settings={settings}
								setSettings={setSettings}
								saveSettings={saveSettings}
								saving={saving}
								scheduleMeta={scheduleMeta}
								wpConfigConstants={wpConfigConstants}
								onDismissConstantNotice={dismissConstantNotice}
							/>
						</Tabs.TabPanel>
						<Tabs.TabPanel tabId={TAB_SETTINGS}>
							<SettingsPanel
								settings={settings}
								setSettings={setSettings}
								saveSettings={saveSettings}
								saving={saving}
							/>
						</Tabs.TabPanel>
					</Tabs>
				</div>
			</section>
		</div>
	);
};
