---
name: Kinetic Flow
colors:
  surface: '#0b1326'
  surface-dim: '#0b1326'
  surface-bright: '#31394d'
  surface-container-lowest: '#060e20'
  surface-container-low: '#131b2e'
  surface-container: '#171f33'
  surface-container-high: '#222a3d'
  surface-container-highest: '#2d3449'
  on-surface: '#dae2fd'
  on-surface-variant: '#bbcabf'
  inverse-surface: '#dae2fd'
  inverse-on-surface: '#283044'
  outline: '#86948a'
  outline-variant: '#3c4a42'
  surface-tint: '#4edea3'
  primary: '#4edea3'
  on-primary: '#003824'
  primary-container: '#10b981'
  on-primary-container: '#00422b'
  inverse-primary: '#006c49'
  secondary: '#ffb95f'
  on-secondary: '#472a00'
  secondary-container: '#ee9800'
  on-secondary-container: '#5b3800'
  tertiary: '#7bd0ff'
  on-tertiary: '#00354a'
  tertiary-container: '#19aee8'
  on-tertiary-container: '#003e55'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#6ffbbe'
  primary-fixed-dim: '#4edea3'
  on-primary-fixed: '#002113'
  on-primary-fixed-variant: '#005236'
  secondary-fixed: '#ffddb8'
  secondary-fixed-dim: '#ffb95f'
  on-secondary-fixed: '#2a1700'
  on-secondary-fixed-variant: '#653e00'
  tertiary-fixed: '#c4e7ff'
  tertiary-fixed-dim: '#7bd0ff'
  on-tertiary-fixed: '#001e2c'
  on-tertiary-fixed-variant: '#004c69'
  background: '#0b1326'
  on-background: '#dae2fd'
  surface-variant: '#2d3449'
  jade-vibrant: '#10b981'
  jade-container: '#064e3b'
  on-jade-container: '#d1fae5'
  amber-signal: '#f59e0b'
  slate-deep: '#0f172a'
  slate-surface: '#1e293b'
typography:
  display-lg:
    fontFamily: Be Vietnam Pro
    fontSize: 72px
    fontWeight: '800'
    lineHeight: 90px
    letterSpacing: -0.02em
  display-md:
    fontFamily: Be Vietnam Pro
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 60px
  headline-lg:
    fontFamily: Be Vietnam Pro
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
  headline-lg-mobile:
    fontFamily: Be Vietnam Pro
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
  headline-md:
    fontFamily: Be Vietnam Pro
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: Be Vietnam Pro
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Be Vietnam Pro
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-lg:
    fontFamily: Be Vietnam Pro
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.05em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  unit: 8px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 48px
  safe-zone-tv: 64px
  touch-target-min: 48px
---

## Brand & Style

This design system is engineered for high-stakes operational clarity in driving education environments, bridging active field instruction with passive lobby information. The brand personality is **Precise, Authoritative, and Kinetic**, prioritizing utilitarian excellence over decorative flourishes.

The aesthetic follows a **High-Contrast / Bold** modern movement, specifically optimized for varied hardware:
- **Instructor Dashboard:** A minimalist approach focusing on functional ergonomics. Large touch targets and high-contrast form elements ensure usability in shifting light conditions inside vehicles.
- **TV Display Board:** A sophisticated dark theme utilizing deep obsidian surfaces and vibrant signal colors (Jade and Amber). This ensures legibility from a distance of 10-15 feet and prevents eye fatigue.

The UI evokes safety and professional progression, utilizing structured layouts that guide the eye toward status indicators and critical action items.

## Colors

The palette is rooted in safety signaling and operational status. The primary color has been updated to a professional **Jade Green**, symbolizing "Go," safety, and successful progression.

- **Primary (Jade #10B981):** Used for primary actions, success states, and "Active" lesson status.
- **Secondary (Amber #F59E0B):** Used for alerts, upcoming appointments, and cautionary status updates.
- **Tertiary (Sky Blue #38BDF8):** Reserved for secondary information and neutral progress tracking.
- **Neutral (Deep Slate #0F172A):** The foundation of the dark theme, providing a stable, low-bloom background for high-brightness screens.

The **TV Display Board** utilizes the dark theme exclusively. Interactive states for primary elements should shift from Jade #10B981 to a brighter #34D399 on hover/active, while "On-Primary" content remains high-contrast (Slate Deep or White).

## Typography

The design system utilizes **Be Vietnam Pro** for its contemporary, approachable, yet highly legible character across digital interfaces.

### Application Rules
- **TV Display:** Use `display-lg` and `display-md` for student queues and room numbers to ensure legibility from 5 meters.
- **Dashboard:** Use `body-lg` for form inputs to accommodate touch-based entry and `headline-md` for section headers.
- **Weight Strategy:** Heavier weights are preferred for data points to maintain glyph integrity on high-brightness TV displays.

## Layout & Spacing

A **Fixed-Fluid Hybrid** model is employed to handle diverse aspect ratios.

### Instructor Dashboard
- Uses an 8-column grid on tablets.
- **Touch Targets:** A strict minimum of 48px for all interactive elements to facilitate one-handed use in moving vehicles.
- **Margins:** 16px safe area to prevent thumb-overlap.

### TV Display Board
- 12-column grid with a **64px safe zone** to account for bezel overscan and physical viewing distance.
- **Vertical Rhythm:** Generous spacing (32px+) is mandatory to prevent visual clutter.
- **Reflow:** On 4K displays, content must split into three distinct vertical columns (Queue, Announcements, Schedule).

## Elevation & Depth

Hierarchy is primarily communicated through **Tonal Layers** and **Luminance Contrast** to ensure performance on lower-end smart TV browsers.

- **Dashboard:** Depth is created via 1px borders and slight background shifts between Slate Deep and Slate Surface. Ambient shadows are used sparingly, limited to Floating Action Buttons (FABs).
- **TV Display:** The aesthetic is flat. "Elevation" is achieved by using the Primary Jade color for active items, creating a visual "pop" against the dark background.
- **Interactive States:** Interactive elements use a primary-container (#064E3B) for base states with a primary (#10B981) border to indicate focus.

## Shapes

The shape language is **Soft (0.25rem)**, reflecting a professional, "tool-like" personality.

- **Dashboard Buttons & Inputs:** 0.25rem (4px) radius for a serious, industrial feel.
- **TV Cards:** Utilize `rounded-lg` (8px) to soften large blocks of high-contrast color and improve visual comfort.
- **Signature & Media:** Containers for webcam feeds and signature pads must maintain strict 4px radii to maximize usable internal area.

## Components

### 1. Interactive Elements
- **Buttons:** 
  - **Primary:** Solid Jade (#10B981) with Slate Deep text.
  - **Secondary:** Transparent with a 2px Jade border.
- **Checkboxes:** Oversized (24x24px) for tablet accessibility.
- **Input Fields:** Large labels positioned above the field; active inputs feature a 2px Jade border.

### 2. Status & Progress
- **Status Chips:** High-visibility badges. "Live" uses a blinking Amber background; "Completed" uses a static Jade border.
- **Progress Bars:** 12px height. Use Amber for "Time Remaining" to signal urgency and Jade for "Task Completion."

### 3. Specialty Modules
- **Signature Pad:** A high-contrast white canvas with a 2px Jade border.
- **Queue List:** Alternating row backgrounds (Slate Deep and Slate Surface). The "Current Student" row is 1.5x scale with a Jade accent left-border.