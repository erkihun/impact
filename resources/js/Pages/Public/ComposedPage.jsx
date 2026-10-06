import PublicLayout from '../../Layouts/PublicLayout';
import PageComposition from '../../Components/Public/PageComposition';
export default function ComposedPage({composition}){return <PublicLayout><div className="status-warning">Private preview. This page is not published.</div><PageComposition composition={composition}/></PublicLayout>;}