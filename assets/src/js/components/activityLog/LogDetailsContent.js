/**
 * Message and trace content for log detail modal / action.
 */
import { __ } from '@wordpress/i18n';

/**
 * @param {Object} props     Props.
 * @param {Object} props.log Log item with optional message, trace.
 * @return {JSX.Element} Message and trace sections or empty state.
 */
export function LogDetailsContent({ log }) {
	if (!log) {
		return null;
	}
	return (
		<div className="updatronix-notes-content updatronix-notes-modal">
			{log.message && (
				<div className="updatronix-notes-section">
					<h4>{__('Message', 'updatronix')}</h4>
					<pre
						className="updatronix-notes-text"
						style={{
							whiteSpace: 'pre-wrap',
							wordBreak: 'break-word',
						}}
					>
						{log.message}
					</pre>
				</div>
			)}
			{log.trace && (
				<details className="updatronix-notes-section">
					<summary className="updatronix-notes-toggle">
						{__('Advanced details', 'updatronix')}
					</summary>
					<pre
						className="updatronix-notes-trace"
						style={{
							whiteSpace: 'pre-wrap',
							wordBreak: 'break-all',
							fontSize: '12px',
						}}
					>
						{log.trace}
					</pre>
				</details>
			)}
			{!log.message && !log.trace && (
				<p>
					{__(
						'No details available for this log entry.',
						'updatronix'
					)}
				</p>
			)}
		</div>
	);
}
