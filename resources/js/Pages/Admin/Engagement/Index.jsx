import WorkspaceLayout from '../../../Layouts/WorkspaceLayout';
import { useWorkspace } from '../../../Components/Workspace/UI';
import { RequestFilters, RequestList } from '../../../Components/Workspace/RequestInbox';

export default function Index({ submissions, counts, filters, types }) {
    const { t } = useWorkspace();
    return <WorkspaceLayout title="Requests" description="Consultation, proposal, partnership and contact requests from the website.">
        <RequestFilters counts={counts} filters={filters} types={types} />
        <div className="ws-split">
            <RequestList submissions={submissions} filters={filters} />
            <div className="ws-wide ws-card ws-empty"><h3>{t('Choose a request')}</h3><p>{t('Open a request to read it, assign it to a colleague or move it forward.')}</p></div>
        </div>
    </WorkspaceLayout>;
}
