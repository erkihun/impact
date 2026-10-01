import { PageHeader } from '../../Components/Public/Common';
import PublicLayout from '../../Layouts/PublicLayout';

export default function SystemStatus({ meta, header, bannerActive, statusMessage, supportUrl, copy }) {
    return (
        <PublicLayout title={meta.title}>
            <PageHeader {...header} />
            <section className="public-section impact-editorial-surface">
                <div className="content-container">
                    <div className="mx-auto max-w-3xl rounded-[var(--m-radius)] bg-white p-6 sm:p-10">
                        <div className={bannerActive ? 'status-warning' : 'status-success'} role="status">{statusMessage}</div>
                        {supportUrl && <a className="button-primary mt-8" href={supportUrl}>{copy.support}</a>}
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
