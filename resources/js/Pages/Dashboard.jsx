import { Link } from '@inertiajs/react';
import WorkspaceLayout from '../Layouts/WorkspaceLayout';
import { Panel,useWorkspace } from '../Components/Workspace/UI';
export default function Dashboard(){const {t}=useWorkspace();return <WorkspaceLayout title="Dashboard"><Panel><p>{t("You're logged in!")}</p><Link className="text-link mt-5" href="/profile">{t('Review profile and security')}</Link></Panel></WorkspaceLayout>;}