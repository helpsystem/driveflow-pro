import type { LessonStatus } from './types';

export interface StatusConfig {
  label: string;
  icon: 'dot' | 'check' | 'x';
  /** neon color for border, glow and badge */
  color: string;
  /** color of the time text */
  time: string;
  pulse?: boolean;      // glowing card border
  blinkBadge?: boolean; // blinking badge
  blinkDot?: boolean;   // blinking dot inside badge
  dim?: boolean;        // faded content (completed)
  strike?: boolean;     // strike-through time (cancelled)
  showClock?: boolean;  // clock icon next to the time
}

export const STATUS: Record<LessonStatus, StatusConfig> = {
  active:    { label: 'Active · On Road',     icon: 'dot',   color: '#22ff88', time: '#ffd60a', showClock: true },
  ready:     { label: 'Ready to Start Class', icon: 'dot',   color: '#a6ff3d', time: '#ffd60a', showClock: true, pulse: true },
  waiting:   { label: 'Awaiting Class Start', icon: 'dot',   color: '#ffd60a', time: '#ffd60a', showClock: true, blinkDot: true },
  upcoming:  { label: 'Upcoming Next',        icon: 'dot',   color: '#ffb020', time: '#ffd60a' },
  cancelled: { label: 'Cancelled',            icon: 'x',     color: '#ff3b57', time: '#b5586a', strike: true, blinkBadge: true },
  completed: { label: 'Completed',            icon: 'check', color: '#4d8dff', time: '#7b8398', dim: true },
};

/** Fuel gauge thresholds: >= 50 green, 25–49 yellow, < 25 red (low) */
export function fuelTone(percent: number) {
  if (percent >= 50) return { color: '#22ff88', low: false };
  if (percent >= 25) return { color: '#ffd60a', low: false };
  return { color: '#ff3b57', low: true };
}
