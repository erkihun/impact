import { Link } from '@inertiajs/react';
import WorkspaceLayout from '../Layouts/WorkspaceLayout';
import { Panel,useWorkspace } from '../Components/Workspace/UI';
export default function Dashboard(){const {t}=useWorkspace();return <WorkspaceLayout title="Today" description="You are signed in to the staff workspace."><Panel><p>{t('Your account does not have access to any workspace areas yet. Ask an administrator for the access you need.')}</p><Link className="text-link mt-5" href="/profile">{t('Review profile and security')}</Link></Panel></WorkspaceLayout>;}