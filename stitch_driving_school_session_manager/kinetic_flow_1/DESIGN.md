---
name: Kinetic Flow
colors:
  surface: '#faf8ff'
  surface-dim: '#d2d9f4'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f3ff'
  surface-container: '#eaedff'
  surface-container-high: '#e2e7ff'
  surface-container-highest: '#dae2fd'
  on-surface: '#131b2e'
  on-surface-variant: '#3e484f'
  inverse-surface: '#283044'
  inverse-on-surface: '#eef0ff'
  outline: '#6e7980'
  outline-variant: '#bdc8d1'
  surface-tint: '#00668a'
  primary: '#00668a'
  on-primary: '#ffffff'
  primary-container: '#38bdf8'
  on-primary-container: '#004965'
  inverse-primary: '#7bd0ff'
  secondary: '#735c00'
  on-secondary: '#ffffff'
  secondary-container: '#fed01b'
  on-secondary-container: '#6f5900'
  tertiary: '#855300'
  on-tertiary: '#ffffff'
  tertiary-container: '#f1a02b'
  on-tertiary-container: '#613b00'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#c4e7ff'
  primary-fixed-dim: '#7bd0ff'
  on-primary-fixed: '#001e2c'
  on-primary-fixed-variant: '#004c69'
  secondary-fixed: '#ffe083'
  secondary-fixed-dim: '#eec200'
  on-secondary-fixed: '#231b00'
  on-secondary-fixed-variant: '#574500'
  tertiary-fixed: '#ffddb8'
  tertiary-fixed-dim: '#ffb960'
  on-tertiary-fixed: '#2a1700'
  on-tertiary-fixed-variant: '#653e00'
  background: '#faf8ff'
  on-background: '#131b2e'
  surface-variant: '#dae2fd'
typography:
  display-lg:
    fontFamily: Vazirmatn
    fontSize: 72px
    fontWeight: '800'
    lineHeight: 90px
    letterSpacing: -0.02em
  display-md:
    fontFamily: Vazirmatn
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 60px
  headline-lg:
    fontFamily: Vazirmatn
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
  headline-md:
    fontFamily: Vazirmatn
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: Vazirmatn
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Vazirmatn
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-lg:
    fontFamily: Vazirmatn
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.05em
  headline-lg-mobile:
    fontFamily: Vazirmatn
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
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
  tv-safe-zone: 64px
---

## Brand & Style

The design system is engineered for high-stakes operational clarity in driving education environments. It bridges the gap between active field instruction and passive lobby information.

The brand personality is **Precise, Authoritative, and Kinetic**. It avoids decorative flourishes in favor of utilitarian excellence. 

### Design Styles
- **Instructor Dashboard:** Uses **Minimalism** with a focus on functional ergonomics. Large touch targets and high-contrast form elements ensure usability in varying light conditions (e.g., inside a vehicle).
- **TV Display Board:** Uses **High-Contrast / Bold** aesthetics. It leverages deep obsidian surfaces and vibrant signal colors (Sky Blue and Amber) to ensure readability from a distance of 10-15 feet.

The UI must evoke a sense of safety and professional progression, utilizing structured layouts that guide the eye toward status indicators and action items.

## Colors

The palette is rooted in safety and signaling. 

- **Sky Blue (#38bdf8):** Used for primary actions, progress indicators, and "In-Progress" states.
- **Amber (#facc15):** Used for alerts, upcoming appointments, and cautionary status updates.
- **Deep Slate (#0f172a):** The foundation for the TV Display Board and the primary text color for the Dashboard to ensure maximum legibility.

In the **Instructor Dashboard**, use a "Soft Light" approach. Surfaces are primarily white with slate text to reduce eye strain during long shifts.
In the **TV Display Board**, the dark theme is mandatory. The deep slate background prevents "blooming" of text on large high-brightness screens, while the blue and amber accents provide immediate visual hierarchy for students waiting in the lobby.

## Typography

This design system uses **Vazirmatn** (or a comparable modern Persian sans-serif) to ensure excellent legibility for RTL scripts.

### RTL Considerations
- All text alignment is `right` by default.
- Font weights are slightly heavier than standard Latin equivalents to maintain the integrity of the Persian glyphs on low-resolution displays or at a distance.

### Application
- **TV Display:** Use `display-lg` and `display-md` for student queues and room numbers. These are designed to be legible from 5 meters away.
- **Dashboard:** Use `body-lg` for form inputs to accommodate touch-based entry and `headline-md` for section headers.
- **Numbers:** Use the Latin numerals subset for technical data (like speed or time) to ensure universal recognition, but use Persian numerals for student names and descriptions.

## Layout & Spacing

The layout philosophy follows a **Fixed-Fluid Hybrid** model.

### Instructor Dashboard (Mobile/Tablet)
- Use a 4-column (mobile) or 8-column (tablet) grid.
- **Touch Targets:** Minimum height of 48px for all interactive elements.
- **Margins:** 16px safe area on all sides to prevent thumb-overlap during one-handed use in a car.

### TV Display Board (1080p/4K)
- Use a 12-column grid with a massive **64px safe zone** (margin) to account for TV bezel "overscan" and physical distance.
- **Rhythm:** Vertical spacing is generous (32px+) to prevent the "wall of text" effect.
- **Content Reflow:** On 4K screens, content should split into three distinct vertical columns (Queue, Announcements, Schedule) rather than stretching one list across the entire width.

## Elevation & Depth

This design system utilizes **Tonal Layers** rather than heavy shadows to maintain performance on lower-end tablets and smart TV browsers.

- **Dashboard:** Depth is communicated via subtle 1px borders (#e2e8f0) and slight background shifts (Level 0: #f8fafc, Level 1: #ffffff). Use a single, soft ambient shadow (0 4px 6px rgba(0,0,0,0.05)) only for floating action buttons like "Start Session."
- **TV Display:** Depth is entirely flat. Hierarchy is created through **Luminance Contrast**. Active items use the Sky Blue background with Slate text, while secondary items use Slate backgrounds with Blue borders.
- **Signature Pads:** These should be treated as "Inset" elements, using a light gray background with a subtle inner shadow to indicate a dedicated writing area.

## Shapes

The shape language is **Soft (0.25rem)**. 

- **Dashboard Buttons:** 0.25rem (4px) corner radius. This provides a professional, "tool-like" feel that is more serious than fully rounded pills.
- **TV Cards:** Use `rounded-lg` (8px) to soften the large high-contrast blocks of color.
- **Webcam Feeds:** Must be strictly rectangular with a 4px radius to maximize the visible frame area for safety monitoring.

## Components

### 1. The Instructor Dashboard
- **Signature Pad:** A full-width white canvas with a 2px Sky Blue border. Include a "Clear" button in the bottom-left and "Confirm" in the bottom-right (RTL-adjusted).
- **Webcam Feed:** 16:9 aspect ratio container with a live "REC" pulse indicator in the top-right corner using a red dot.
- **Input Fields:** Large labels placed *above* the input. Inputs must have a 2px active border in Sky Blue.

### 2. The TV Display Board
- **Status Chips:** High-visibility badges. "Live" uses a blinking Amber background; "Completed" uses a static Sky Blue border.
- **Queue List:** High-contrast rows with alternating background shades (#0f172a and #1e293b). The "Current Student" row should be 1.5x the height of others.

### 3. Shared Elements
- **Buttons:**
  - *Primary:* Solid Sky Blue with Deep Slate text.
  - *Secondary:* Transparent with a 2px Sky Blue border.
- **Progress Bars:** Thick (12px height) bars. Use Amber for "Minutes Remaining" to create a sense of urgency as the lesson nears completion.
- **Checkboxes:** Larger than standard (24x24px) for easy tapping on tablets.