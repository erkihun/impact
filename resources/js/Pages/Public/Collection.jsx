import Reveal from '../../Components/Reveal';
import AppLink from '../../Components/AppLink';
import { EmptyState, Pagination } from '../../Components/Public/Common';
import PublicLayout, { PageIntro } from '../../Layouts/PublicLayout';

function Card({ item, copy, index }) {
    return (
        <Reveal as="article" index={index % 3} className={`m-card ${item.featured ? 'm-card-featured' : ''}`}>
            <div className="m-card-meta">
                <span className="m-index">{item.number}</span>
                {item.date && <time dateTime={item.datetime}>{item.date}</time>}
            </div>
            <h2><AppLink href={item.href}>{item.name}</AppLink></h2>
            {item.summary && <p>{item.summary}</p>}
            <span className="m-link" aria-hidden="true">{copy.learnMore}</span>
        </Reveal>
    );
}

function Person({ item, copy, index }) {
    return (
        <Reveal as="article" index={index % 4} className="m-card m-person">
            <div className="m-person-photo">
                {item.photo
                    ? <img src={item.photo} alt={item.photoAlt} loading="lazy" decoding="async" />
                    : <span aria-hidden="true">{item.initial}</span>}
            </div>
            <div className="m-person-body">
                <h2><AppLink href={item.href}>{item.name}</AppLink></h2>
                {item.title && <p>{item.title}</p>}
                <span className="m-link mt-auto" aria-hidden="true">{copy.viewProfile}</span>
            </div>
        </Reveal>
    );
}

export default function Collection({ meta, breadcrumbs, composition, header, people, items, pagination, copy }) {
    const Item = people ? Person : Card;

    return (
        <PublicLayout title={meta.title} description={meta.description} breadcrumbs={breadcrumbs}>
            <PageIntro composition={composition} header={header} />

            <section className="m-section m-band" aria-label={header.title}>
                <div className="content-container">
                    {items.length > 0 ? (
                        <div className={`m-collection ${people ? 'm-collection-people' : ''}`}>
                            {items.map((item, index) => <Item key={item.href} item={item} copy={copy} index={index} />)}
                        </div>
                    ) : (
                        <EmptyState title={copy.emptyTitle} description={copy.emptyDescription}>
                            <AppLink className="button-primary" href={copy.emptyHref}>{copy.emptyAction}</AppLink>
                        </EmptyState>
                    )}
                    <Pagination pagination={pagination} copy={copy} />
                </div>
            </section>
        </PublicLayout>
    );
}
