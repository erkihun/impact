import { Link } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import { Editor, Action, field, useWorkspace } from '../../Components/Workspace/UI';
export default function Register(){const {t}=useWorkspace();return <AuthLayout title="Secure staff access"><p>{t('Staff access is invitation-only, permission-aware and protected by multifactor authentication where required.')}</p><Link className="text-link mt-5" href="/login">{t('Log in')}</Link></AuthLayout>;}