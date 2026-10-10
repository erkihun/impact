import { useWorkspace, Status } from './UI';
import Icon from '../Icon';
export default function Files({ files = [] }) {
    const { t } = useWorkspace();
    return <ul className="grid gap-2">{files.map(file => <li key={file.id} className="ws-file">
        <Icon name="file" />
        <span><strong className="font-semibold">{file.media_asset?.original_name ?? file.classification}</strong>{file.download_url ? <small className="ws-sub">{t('Scanned · safe to open')}</small> : <small className="ws-sub">{t('The file remains unavailable to staff until security processing is complete.')}</small>}</span>
        <Status value={file.media_asset?.scan_status} />
        {file.download_url && <a className="text-link" href={file.download_url}>{t('Download')}</a>}
    </li>)}</ul>;
}
