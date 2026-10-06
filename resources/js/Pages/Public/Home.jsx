import { motion } from 'framer-motion';
import HeroSlider from '../../Components/Public/HeroSlider';
import { ResponsiveImage } from '../../Components/Public/Common';
import AppLink from '../../Components/AppLink';
import Icon from '../../Components/Icon';
import Reveal from '../../Components/Reveal';
import PublicLayout from '../../Layouts/PublicLayout';
import { pad } from '../../lib/format';

const MotionLink = motion.create(AppLink);

function SectionLead({ eyebrow, title, lead, id, children }) {
    return (
        <Reveal className="m-center">
            {eyebrow && <p className="m-eyebrow">{eyebrow}</p>}
            <h2 id={id} className="m-title">{title}</h2>
            {lead && <p className="m-lead">{lead}</p>}
            {children}
        </Reveal>
    );
}

function Ledger({ items, copy }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <section className="m-proof-rail" aria-labelledby="home-proof-title">
            <div className="content-container">
                <SectionLead id="home-proof-title" title={copy.ledgerTitle} />
                <div className="m-stats">
                    {items.map((item, index) => {
                        const Tag = item.href ? 'a' : 'div';

                        return (
                            <Reveal key={item.label} index={index}>
                                <Tag className="m-stat" href={item.href ?? undefined}>
                                    <span className="m-stat-number">{item.value}</span>
                                    <strong>{item.label}</strong>
                                    <small>{item.context}</small>
                                </Tag>
                            </Reveal>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

function ExpertPerspective({ experts, copy }) {
    if (experts.length === 0) {
        return null;
    }

    return (
        <section className="m-section m-experts-section" aria-labelledby="home-perspectives-title">
            <div className="content-container">
                <SectionLead id="home-perspectives-title" eyebrow={copy.perspectivesEyebrow} title={copy.perspectivesTitle} />
                <div className={`m-grid ${experts.length > 2 ? 'm-grid-3' : 'm-grid-2'} m-expert-grid`}>
                    {experts.map((expert, index) => (
                        <Reveal key={expert.href} index={index} className="flex">
                            <AppLink className="m-expert-card" href={expert.href}>
                                <ExpertCard item={expert} copy={copy} />
                            </AppLink>
                        </Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}

function Portfolio({ projects, copy, links }) {
    if (! projects.length) return null;

    return (
        <section className="m-section" aria-labelledby="home-portfolio-title">
            <div className="content-container">
                <div className="m-section-heading">
                    <SectionLead id="home-portfolio-title" eyebrow={copy.caseEyebrow} title={copy.portfolioTitle} />
                    <AppLink className="m-link" href={links.caseStudies}>{copy.caseStudies}</AppLink>
                </div>
                <div className="m-grid m-grid-3 mt-8">
                    {projects.map((project, index) => (
                        <Reveal as="article" className="m-card m-project-card" key={project.href} index={index}>
                            <span className="m-index">{pad(index + 1)}</span>
                            <h3><AppLink href={project.href}>{project.title}</AppLink></h3>
                            {project.summary && <p>{project.summary}</p>}
                            <AppLink className="m-link" href={project.href}>{copy.caseLink}</AppLink>
                        </Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}

function Articles({ articles, copy, links }) {
    if (! articles.length) return null;

    return (
        <section className="m-section m-band" aria-labelledby="home-insight-title">
            <div className="content-container">
                <div className="m-section-heading">
                    <SectionLead id="home-insight-title" eyebrow={copy.insight} title={copy.insightsTitle} />
                    <AppLink className="m-link" href={links.insights}>{copy.allArticles}</AppLink>
                </div>
                <Tiles items={articles} renderItem={(item) => <InsightCard item={item} copy={copy} />} />
            </div>
        </section>
    );
}

function FinalCta({ copy, links }) {
    return (
        <section className="m-section m-final-cta-section" aria-labelledby="home-final-cta-title">
            <div className="content-container">
                <Reveal>
                    <div className="m-final-cta">
                        <p className="m-eyebrow">{copy.finalCtaEyebrow}</p>
                        <h2 id="home-final-cta-title">{copy.finalCtaTitle}</h2>
                        <p>{copy.finalCtaLead}</p>
                        <div className="m-links">
                            <MotionLink className="m-btn" href={links.consultation} whileTap={{ scale: 0.97 }}>{copy.requestConsultation}</MotionLink>
                            <AppLink className="m-link" href={links.services}>{copy.servicesLink}</AppLink>
                        </div>
                    </div>
                </Reveal>
            </div>
        </section>
    );
}

function Services({ services, copy, links }) {
    if (services.length === 0) {
        return null;
    }

    return (
        <section className="m-section m-band m-services-section" aria-labelledby="home-services-title">
            <div className="content-container m-split">
                <Reveal className="m-split-lead">
                    <p className="m-eyebrow">{copy.servicesEyebrow}</p>
                    <h2 id="home-services-title" className="m-title">{copy.servicesTitle}</h2>
                    <p className="m-lead">{copy.servicesLead}</p>
                    <AppLink className="m-link" href={links.services}>{copy.servicesLink}</AppLink>
                </Reveal>
                <ol className="m-service-cards">
                    {services.map((service, index) => (
                        <Reveal as="li" key={service.href} index={index}>
                            <MotionLink className="m-service-card" href={service.href} whileTap={{ scale: 0.99 }}>
                                <span className="m-service-card-top" aria-hidden="true">
                                    <span className="m-row-number">{pad(index + 1)}</span>
                                    <Icon name="arrow-up-right" className="size-5" />
                                </span>
                                <h3>{service.name}</h3>
                                <p>{service.summary}</p>
                                <span className="m-service-card-link">{copy.learnMore}</span>
                            </MotionLink>
                        </Reveal>
                    ))}
                </ol>
            </div>
        </section>
    );
}

function CaseStudy({ caseStudy, copy, links }) {
    if (! caseStudy) {
        return null;
    }

    return (
        <section className="m-section" aria-labelledby="home-case-title">
            <div className="content-container">
                <Reveal as="article" className="m-feature">
                    <div className="m-feature-copy">
                        <p className="m-eyebrow">{copy.caseEyebrow}</p>
                        <h2 id="home-case-title">{caseStudy.title}</h2>
                        {caseStudy.outcomes && <p>{caseStudy.outcomes}</p>}
                        <div className="m-links">
                            <AppLink className="m-link" href={caseStudy.href}>{copy.caseLink}</AppLink>
                            <AppLink className="m-link" href={links.caseStudies}>{copy.caseStudies}</AppLink>
                        </div>
                    </div>
                    <motion.div
                        className="m-feature-media"
                        initial={{ y: 60 }}
                        whileInView={{ y: 0 }}
                        viewport={{ once: true }}
                        transition={{ duration: 1, ease: [0.2, 0.8, 0.2, 1] }}
                    >
                        <img src={caseStudy.image} width="1536" height="1024" alt="" loading="lazy" decoding="async" />
                    </motion.div>
                </Reveal>
            </div>
        </section>
    );
}

function Testimonials({ testimonials, copy }) {
    if (! testimonials?.length) {
        return null;
    }

    const [lead, ...supporting] = testimonials;

    return (
        <section className="m-section m-testimonials-section" aria-labelledby="home-testimonials-title">
            <div className="content-container">
                <SectionLead id="home-testimonials-title" eyebrow={copy.testimonialsEyebrow} title={copy.testimonialsTitle} />
                <div className="m-testimonials">
                    <Reveal className="m-testimonial-lead">
                        <motion.figure
                            className="m-testimonial-card m-testimonial-card-lead"
                            initial={{ opacity: 0, y: 28, rotateX: 5 }}
                            whileInView={{ opacity: 1, y: 0, rotateX: 0 }}
                            viewport={{ once: true, margin: '0px 0px -12% 0px' }}
                            transition={{ type: 'spring', stiffness: 140, damping: 18 }}
                        >
                            <span className="m-testimonial-mark" aria-hidden="true">“</span>
                            <blockquote>{lead.quote}</blockquote>
                            <figcaption>
                                <strong>{lead.name}</strong>
                                <span>{lead.role}</span>
                            </figcaption>
                            {lead.metric && <p className="m-testimonial-metric">{lead.metric}</p>}
                        </motion.figure>
                    </Reveal>
                    {supporting.length > 0 && (
                        <div className="m-testimonial-list">
                            {supporting.map((item, index) => (
                                <Reveal key={`${item.name}-${index}`} index={index}>
                                    <motion.figure
                                        className="m-testimonial-card"
                                        whileHover={{ y: -5, rotate: index % 2 === 0 ? -0.3 : 0.3 }}
                                        transition={{ type: 'spring', stiffness: 260, damping: 22 }}
                                    >
                                        <blockquote>{item.quote}</blockquote>
                                        <figcaption>
                                            <strong>{item.name}</strong>
                                            <span>{item.role}</span>
                                        </figcaption>
                                        {item.metric && <p className="m-testimonial-metric">{item.metric}</p>}
                                    </motion.figure>
                                </Reveal>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}

function Tiles({ items, renderItem, columns = 3, className = '' }) {
    return (
        <div className={`m-grid ${columns > 2 ? 'm-grid-3' : 'm-grid-2'} mt-14 ${className}`}>
            {items.map((item, index) => (
                <Reveal key={item.href} index={index} className="flex">
                    <MotionLink
                        className="m-tile w-full"
                        href={item.href}
                        whileHover={{ y: -4 }}
                        transition={{ type: 'spring', stiffness: 300, damping: 24 }}
                    >
                        {renderItem(item, index)}
                    </MotionLink>
                </Reveal>
            ))}
        </div>
    );
}

function ExpertCard({ item, copy }) {
    return (
        <>
            <div className={`m-expert-cover${item.photo ? '' : ' m-expert-cover-initials'}`}>
                {item.photo ? (
                    <ResponsiveImage src={item.photo} srcSet={item.photoSrcset} sizes={item.photoSizes} width={item.photoWidth} height={item.photoHeight} alt={item.photoAlt || item.name} />
                ) : (
                    <span className="m-expert-initial" aria-hidden="true">{item.initial}</span>
                )}
            </div>
            <div className="m-expert-body">
                <h3>{item.name}</h3>
                {item.title && <p className="m-expert-role">{item.title}</p>}
                <span className="m-expert-action">
                    {copy.viewExpert}
                    <span className="m-expert-arrow" aria-hidden="true">&#8599;</span>
                </span>
            </div>
        </>
    );
}

function InsightCard({ item, copy }) {
    return (
        <>
            <span className="m-index">{copy.insight}</span>
            <h3>{item.title}</h3>
            {item.excerpt && <p>{item.excerpt}</p>}
            <span className="m-link">{copy.readInsight}</span>
        </>
    );
}

export default function Home({ heroSlider, ledger, services, projects = [], articles = [], testimonials = [], industries, experts = [], copy, links }) {
    return (
        <PublicLayout>
            {heroSlider.slides.length > 0 && <HeroSlider slider={heroSlider} stats={ledger} statsLabel={copy.ledgerTitle} />}

            {heroSlider.slides.length === 0 && <Ledger items={ledger} copy={copy} />}
            <Services services={services} copy={copy} links={links} />
            <Portfolio projects={projects} copy={copy} links={links} />
            <Articles articles={articles} copy={copy} links={links} />
            <Testimonials testimonials={testimonials} copy={copy} />
            <ExpertPerspective experts={experts} copy={copy} />

            {industries.length > 0 && (
                <section className="m-section m-band" aria-labelledby="home-industries-title">
                    <div className="content-container">
                        <SectionLead id="home-industries-title" eyebrow={copy.industriesEyebrow} title={copy.industriesTitle} lead={copy.industriesLead} />
                        <Tiles
                            items={industries}
                            columns={industries.length > 2 ? 3 : 2}
                            renderItem={(industry) => (
                                <>
                                    <h3>{industry.name}</h3>
                                    {industry.summary && <p>{industry.summary}</p>}
                                    <span className="m-link">{copy.learnMore}</span>
                                </>
                            )}
                        />
                    </div>
                </section>
            )}

            <section className="m-section m-band" aria-labelledby="home-engagement-title">
                <div className="content-container">
                    <SectionLead id="home-engagement-title" eyebrow={copy.engagementEyebrow} title={copy.engagementTitle} lead={copy.engagementLead} />
                    <ol className="m-process">
                        {copy.steps.map((step, index) => (
                            <Reveal as="li" key={step} index={index}>
                                <span>{index + 1}</span>
                                <strong>{step}</strong>
                            </Reveal>
                        ))}
                    </ol>
                    <Reveal className="m-links mt-12">
                        <MotionLink className="m-btn" href={links.consultation} whileTap={{ scale: 0.97 }}>{copy.requestConsultation}</MotionLink>
                        <AppLink className="m-link" href={links.contact}>{copy.otherRoute}</AppLink>
                    </Reveal>
                </div>
            </section>
            <FinalCta copy={copy} links={links} />
        </PublicLayout>
    );
}
