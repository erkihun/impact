import { motion } from 'framer-motion';

const EASE = [0.2, 0.8, 0.2, 1];

// Calm fade-up as content enters the viewport. MotionConfig's
// reducedMotion="user" strips the movement for visitors who ask for less.
export default function Reveal({ as = 'div', index = 0, children, ...props }) {
    const Component = motion[as] ?? motion.div;

    return (
        <Component
            initial={{ opacity: 0, y: 28 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: '0px 0px -8% 0px' }}
            transition={{ duration: 0.8, ease: EASE, delay: index * 0.08 }}
            {...props}
        >
            {children}
        </Component>
    );
}
