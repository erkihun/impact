import { motion } from 'framer-motion';
import HeroSlider from '../../Components/Public/HeroSlider';
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
        <section className="m-section" aria-labelledby="home-proof-title">
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

function Services({ services, copy, links }) {
    if (services.length === 0) {
        return null;
    }

    return (
        <section className="m-section m-band" aria-labelledby="home-services-title">
            <div className="content-container m-split">
                <Reveal className="m-split-lead">
                    <p className="m-eyebrow">{copy.servicesEyebrow}</p>
                    <h2 id="home-services-title" className="m-title">{copy.servicesTitle}</h2>
                    <p className="m-lead">{copy.servicesLead}</p>
                    <AppLink className="m-link" href={links.services}>{copy.servicesLink}</AppLink>
                </Reveal>
                <ol className="m-rows">
                    {services.map((service, index) => (
                        <Reveal as="li" key={service.href} index={index}>
                            <MotionLink className="m-row" href={service.href} whileTap={{ scale: 0.99 }}>
                                <span className="m-row-number" aria-hidden="true">{pad(index + 1)}</span>
                                <span className="m-row-body">
                                    <h3>{service.name}</h3>
                                    <span className="m-row-text">{service.summary}</span>
                                </span>
                                <span className="m-row-arrow" aria-hidden="true">
                                    <Icon name="arrow-right" className="size-4" strokeWidth={2} />
                                </span>
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
                    <p className="m-eyebrow">{copy.caseEyebrow}</p>
                    <h2 id="home-case-title">{caseStudy.title}</h2>
                    {caseStudy.outcomes && <p>{caseStudy.outcomes}</p>}
                    <div className="m-links">
                        <AppLink className="m-link" href={caseStudy.href}>{copy.caseLink}</AppLink>
                        <AppLink className="m-link" href={links.caseStudies}>{copy.caseStudies}</AppLink>
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

function ExpertCard({ item, copy, index = 0 }) {
    return (
        <>
            <motion.span
                className="m-avatar m-expert-avatar"
                initial={{ opacity: 0, y: 18, scale: 0.88 }}
                whileInView={{ opacity: 1, y: 0, scale: 1 }}
                viewport={{ once: true, margin: '0px 0px -10% 0px' }}
                transition={{ type: 'spring', stiffness: 180, damping: 18, delay: index * 0.08 }}
            >
                {item.photo ? <img src={item.photo} alt={item.photoAlt} loading="lazy" decoding="async" /> : <span aria-hidden="true">{item.initial}</span>}
            </motion.span>
            <span className="m-index">{copy.perspectivesEyebrow}</span>
            <h3>{item.name}</h3>
            <p>{item.title}</p>
            <span className="m-link">{copy.meetTeam}</span>
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

export default function Home({ meta, heroSlider, ledger, services, caseStudy, industries, experts = [], insight, copy, links }) {
    return (
        <PublicLayout title={meta?.title}>
            {heroSlider.slides.length > 0 && <HeroSlider slider={heroSlider} />}

            <Ledger items={ledger} copy={copy} />
            <Services services={services} copy={copy} links={links} />
            <CaseStudy caseStudy={caseStudy} copy={copy} links={links} />

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

            {(experts.length > 0 || insight) && (
                <section className="m-section" aria-labelledby="home-perspectives-title">
                    <div className="content-container">
                        <SectionLead id="home-perspectives-title" eyebrow={copy.perspectivesEyebrow} title={copy.perspectivesTitle} />
                        {experts.length > 0 && (
                            <Tiles
                                items={experts.map((expert) => ({ ...expert, kind: 'expert' }))}
                                columns={experts.length > 2 ? 3 : 2}
                                className="m-expert-grid"
                                renderItem={(item, index) => <ExpertCard item={item} copy={copy} index={index} />}
                            />
                        )}
                        {insight && (
                            <Reveal className="m-insight-feature">
                                <MotionLink
                                    className="m-tile m-insight-tile"
                                    href={insight.href}
                                    whileHover={{ y: -4 }}
                                    transition={{ type: 'spring', stiffness: 300, damping: 24 }}
                                >
                                    <InsightCard item={insight} copy={copy} />
                                </MotionLink>
                            </Reveal>
                        )}
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
        </PublicLayout>
    );
}
