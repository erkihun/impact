import Reveal from '../../Components/Reveal';
import AppLink from '../../Components/AppLink';
import PublicLayout, { PageIntro } from '../../Layouts/PublicLayout';
import { pad } from '../../lib/format';

export default function About({ breadcrumbs, composition, header, principles, links, copy }) {
    return (
        <PublicLayout breadcrumbs={breadcrumbs}>
            <div className="m-about-page">
            <PageIntro composition={composition} header={header}>
                <AppLink className="button-primary" href={links.experts}>{copy.meetExperts}</AppLink>
                <AppLink className="button-secondary" href={links.caseStudies}>{copy.reviewWork}</AppLink>
            </PageIntro>

            <section className="m-section m-band m-about-commitments" aria-labelledby="institutional-commitments-heading">
                <div className="content-container">
                    <Reveal className="m-center">
                        <p className="m-eyebrow">{copy.commitmentsEyebrow}</p>
                        <h2 id="institutional-commitments-heading" className="m-title">{copy.commitmentsTitle}</h2>
                        <p className="m-lead m-about-prose">{copy.commitmentsLead}</p>
                    </Reveal>
                    <div className="m-grid m-grid-3 mt-14">
                        {principles.map((principle, index) => (
                            <Reveal as="article" key={principle.title} index={index} className="m-tile m-about-principle">
                                <span className="m-index">{pad(index + 1)}</span>
                                <h3>{principle.title}</h3>
                                <p className="m-about-prose">{principle.text}</p>
                            </Reveal>
                        ))}
                    </div>
                </div>
            </section>

            <section className="m-section" aria-labelledby="about-next-title">
                <div className="content-container">
                    <Reveal className="m-center">
                        <h2 id="about-next-title" className="m-title">{copy.nextTitle}</h2>
                        <div className="m-links">
                            <AppLink className="m-btn" href={links.consultation}>{copy.requestConsultation}</AppLink>
                            <AppLink className="m-link" href={links.experts}>{copy.meetExperts}</AppLink>
                        </div>
                    </Reveal>
                </div>
            </section>
            </div>
        </PublicLayout>
    );
}
