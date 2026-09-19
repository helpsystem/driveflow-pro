'use client';

import { useCallback, useEffect, useRef, useState, type CSSProperties } from 'react';
import type { Lesson } from './types';
import { STATUS, fuelTone } from './status';

const DISPLAY = 'font-[family-name:var(--font-display)]';

const BACKGROUND = [
  'radial-gradient(1px 1px at 12% 22%, #fff8 50%, transparent 51%)',
  'radial-gradient(1px 1px at 78% 14%, #fff6 50%, transparent 51%)',
  'radial-gradient(1.5px 1.5px at 35% 68%, #fff5 50%, transparent 51%)',
  'radial-gradient(1px 1px at 90% 72%, #fff7 50%, transparent 51%)',
  'radial-gradient(1px 1px at 55% 40%, #fff5 50%, transparent 51%)',
  'radial-gradient(ellipse at 50% 0%, #0d1a38 0%, #050a17 60%)',
].join(',');

interface Props {
  initialLessons: Lesson[];
  announcement?: string;
  /** GET endpoint returning Lesson[]; polled every pollSeconds */
  endpoint?: string;
  perPage?: number;
  pageSeconds?: number;
  pollSeconds?: number;
}

/* ---------------- Icons ---------------- */
const Svg = ({ d, className, fill = true }: { d: string; className?: string; fill?: boolean }) => (
  <svg viewBox="0 0 24 24" className={className} fill={fill ? 'currentColor' : 'none'} stroke={fill ? undefined : 'currentColor'} strokeWidth={fill ? undefined : 2.6} strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <path d={d} />
  </svg>
);
const CAP = 'M12 3 1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.55L12 15l5-2.56v3.55z';
const PUMP = 'M19.77 7.23l.01-.01-3.72-3.72L15 4.56l2.11 2.11c-.94.36-1.61 1.26-1.61 2.33 0 1.38 1.12 2.5 2.5 2.5.36 0 .69-.08 1-.21v7.21c0 .55-.45 1-1 1s-1-.45-1-1V14c0-1.1-.9-2-2-2h-1V5c0-1.1-.9-2-2-2H6c-1.1 0-2 .9-2 2v16h10v-7.5h1.5v5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V9c0-.69-.28-1.32-.73-1.77zM12 10H6V5h6v5zm6 0c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z';
const SOUND = 'M3 9v6h4l5 5V4L7 9H3zm13.5 3A4.5 4.5 0 0 0 14 7.97v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z';
const BELL = 'M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z';
const FULL = 'M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z';

/* ---------------- Maryland plate ---------------- */
function MarylandPlate({ plate, muted }: { plate: string; muted?: boolean }) {
  return (
    <div
      className={`${DISPLAY} relative flex h-[5.4rem] w-[15.5rem] flex-none items-end justify-center rounded-md bg-gradient-to-b from-white to-[#dfe4ee] pb-1 font-bold text-[#0f1c4d] shadow-[0_.2rem_.6rem_rgba(0,0,0,.6),inset_0_0_0_.16rem_#c9d0de] ${muted ? '[filter:saturate(.4)_brightness(.8)]' : ''}`}
    >
      <span className="absolute inset-x-0 top-1 text-center text-[.95rem] uppercase tracking-[.06em] text-[#b0141e]">Maryland</span>
      <span className="text-[3.3rem] leading-none tracking-[.03em]">{plate}</span>
      <span className="absolute left-[.7rem] top-[.45rem] h-2 w-2 rounded-full bg-[#222]" />
      <span className="absolute right-[.7rem] top-[.45rem] h-2 w-2 rounded-full bg-[#222]" />
    </div>
  );
}

/* ---------------- Fuel gauge (next to the plate) ---------------- */
function FuelGauge({ percent }: { percent?: number }) {
  if (percent == null) return null;
  const p = Math.max(0, Math.min(100, Math.round(percent)));
  const tone = fuelTone(p);
  const lit = p > 0 ? Math.max(1, Math.round(p / 10)) : 0;

  return (
    <div
      className={`lb-fuel ${tone.low ? 'low' : ''} flex h-[5.4rem] w-[11rem] flex-none flex-col justify-center gap-[.5rem] rounded-lg bg-[#060c18] px-3`}
      style={{ '--f': tone.color } as CSSProperties}
      role="meter"
      aria-label="Fuel level"
      aria-valuemin={0}
      aria-valuemax={100}
      aria-valuenow={p}
    >
      <div className="flex items-center justify-between">
        <Svg d={PUMP} className="h-[1.5rem] w-[1.5rem]" />
        <span className={`lb-fuel-pct ${DISPLAY} text-[1.8rem] font-bold leading-none tabular-nums`}>{p}%</span>
      </div>
      <div className="grid grid-cols-10 gap-[.2rem]">
        {Array.from({ length: 10 }, (_, i) => (
          <i key={i} className={`lb-fuel-seg block h-[.9rem] rounded-[.15rem] ${i < lit ? 'on' : ''}`} />
        ))}
      </div>
      <div className={`${DISPLAY} flex justify-between text-[.8rem] font-medium leading-none tracking-wide text-[#6d7893]`}>
        <span>E</span>
        <span>{tone.low ? 'LOW FUEL' : 'FUEL'}</span>
        <span>F</span>
      </div>
    </div>
  );
}

/* ---------------- Lesson card ---------------- */
function LessonCard({ lesson: l }: { lesson: Lesson }) {
  const cfg = STATUS[l.status];
  const dim = cfg.dim ? 'opacity-[.55]' : '';

  return (
    <article
      className={`lb-card ${cfg.pulse ? 'lb-pulse' : ''} flex flex-col overflow-hidden rounded-[1.3rem]`}
      style={{ '--c': cfg.color } as CSSProperties}
    >
      <div className="lb-divider flex flex-col items-center gap-[.9rem] px-[1.3rem] py-[1.2rem]">
        <div className={`flex w-full items-center justify-between gap-3 ${dim}`}>
          <MarylandPlate plate={l.plate} muted={cfg.strike} />
          <FuelGauge percent={l.fuel} />
        </div>

        <span className={`lb-badge ${DISPLAY} ${cfg.blinkBadge ? 'lb-blink' : ''} inline-flex items-center gap-2 whitespace-nowrap rounded-full px-4 py-[.45rem] text-[1.1rem] font-bold uppercase tracking-[.04em]`}>
          {cfg.icon === 'dot' && <span className={`lb-dot h-[.6rem] w-[.6rem] rounded-full ${cfg.blinkDot ? 'lb-blink' : ''}`} />}
          {cfg.icon === 'check' && <Svg fill={false} d="M5 12.5l4.5 4.5L19 7.5" className="h-4 w-4" />}
          {cfg.icon === 'x' && <Svg fill={false} d="M6 6l12 12M18 6L6 18" className="h-4 w-4" />}
          {cfg.label}
        </span>
      </div>

      <div className={`flex flex-1 flex-col items-center justify-center gap-4 px-4 py-5 text-center ${dim}`}>
        <Svg d={CAP} className="h-12 w-12 text-[#6d7893]" />
        <div className={`${DISPLAY} max-w-full break-words text-[3.6rem] font-bold leading-[1.05] text-[#f4f7ff]`}>{l.student}</div>
        <div className="inline-flex items-center gap-2 rounded-full bg-[#1f5fe0] px-5 py-[.45rem] text-[1.5rem] text-[#eaf0ff] shadow-[0_0_1rem_rgba(31,95,224,.4)]">
          🧑‍🏫 {l.instructor}
        </div>
        <div className="inline-flex items-center gap-2 rounded-full bg-[#3a414f] px-5 py-[.45rem] text-[1.5rem] text-[#d5dbe8]">
          🚗 {l.lesson}
        </div>
      </div>

      <div
        className={`${DISPLAY} mx-[1.3rem] mb-[1.3rem] flex items-center justify-center gap-3 rounded-xl bg-[#060c18] p-4 text-[2.5rem] font-medium tabular-nums ${dim}`}
        style={{ color: cfg.time }}
      >
        {cfg.showClock && (
          <svg viewBox="0 0 24 24" className="h-[2.2rem] w-[2.2rem]" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden>
            <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
          </svg>
        )}
        <span className={cfg.strike ? 'line-through decoration-[#ff3b57] decoration-[.18rem]' : ''}>
          {l.start} – {l.end}
        </span>
      </div>
    </article>
  );
}

/* ---------------- Board ---------------- */
export default function LobbyBoard({
  initialLessons,
  announcement = '',
  endpoint,
  perPage = 3,
  pageSeconds = 10,
  pollSeconds = 15,
}: Props) {
  const [lessons, setLessons] = useState<Lesson[]>(initialLessons);
  const [now, setNow] = useState<Date | null>(null);
  const [page, setPage] = useState(0);
  const [fading, setFading] = useState(false);
  const [soundOn, setSoundOn] = useState(true);

  const audioRef = useRef<AudioContext | null>(null);
  const prevStatus = useRef<Map<string, string>>(new Map(initialLessons.map((l) => [l.id, l.status])));

  const pages = Math.max(1, Math.ceil(lessons.length / perPage));
  const safePage = page % pages;
  const visible = lessons.slice(safePage * perPage, safePage * perPage + perPage);

  /* Scale rem with the screen width (1rem = 16px at 1920px) — restored on unmount */
  useEffect(() => {
    const root = document.documentElement;
    const old = root.style.fontSize;
    root.style.fontSize = 'clamp(8px, 0.8334vw, 40px)';
    return () => { root.style.fontSize = old; };
  }, []);

  /* Clock */
  useEffect(() => {
    setNow(new Date());
    const t = setInterval(() => setNow(new Date()), 1000);
    return () => clearInterval(t);
  }, []);

  /* Page rotation */
  useEffect(() => {
    if (pages <= 1) return;
    const t = setInterval(() => {
      setFading(true);
      setTimeout(() => { setPage((p) => (p + 1) % pages); setFading(false); }, 450);
    }, pageSeconds * 1000);
    return () => clearInterval(t);
  }, [pages, pageSeconds]);

  /* Chime */
  const chime = useCallback(() => {
    if (!soundOn) return;
    try {
      const Ctx = window.AudioContext || (window as any).webkitAudioContext;
      const ctx = (audioRef.current ??= new Ctx());
      [880, 1175].forEach((f, i) => {
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        const t0 = ctx.currentTime + i * 0.25;
        o.type = 'sine'; o.frequency.value = f;
        g.gain.setValueAtTime(0.0001, t0);
        g.gain.exponentialRampToValueAtTime(0.25, t0 + 0.03);
        g.gain.exponentialRampToValueAtTime(0.0001, t0 + 0.6);
        o.connect(g); g.connect(ctx.destination);
        o.start(t0); o.stop(t0 + 0.65);
      });
    } catch { /* audio is blocked until the first user interaction */ }
  }, [soundOn]);

  /* Live data + chime when a lesson becomes "ready" */
  useEffect(() => {
    if (!endpoint) return;
    let alive = true;
    const load = async () => {
      try {
        const r = await fetch(endpoint, { cache: 'no-store' });
        if (!r.ok || !alive) return;
        const next: Lesson[] = await r.json();
        const becameReady = next.some((l) => l.status === 'ready' && prevStatus.current.get(l.id) !== 'ready');
        prevStatus.current = new Map(next.map((l) => [l.id, l.status]));
        setLessons(next);
        if (becameReady) chime();
      } catch { /* keep the last known data */ }
    };
    load();
    const t = setInterval(load, pollSeconds * 1000);
    return () => { alive = false; clearInterval(t); };
  }, [endpoint, pollSeconds, chime]);

  const toggleFullscreen = () => {
    if (document.fullscreenElement) document.exitFullscreen();
    else document.documentElement.requestFullscreen?.();
  };

  const tool = 'grid h-[3.6rem] w-[3.6rem] place-items-center rounded-lg border border-[#1a2642] bg-[#111a30] text-[#b8c3dc] hover:border-[#3a4c7a] hover:text-white focus-visible:outline-none focus-visible:border-[#3a4c7a]';

  return (
    <div
      className="flex h-screen flex-col gap-[1.6rem] overflow-hidden px-[2.2rem] pt-[1.6rem] font-[family-name:var(--font-body)] text-[#f4f7ff]"
      style={{ background: BACKGROUND, backgroundColor: '#050a17' }}
    >
      {/* Header */}
      <header className="grid grid-cols-[1fr_auto_1fr] items-center rounded-[1.1rem] border border-[#1a2642] bg-gradient-to-b from-[#0c1428] to-[#09101f] px-[1.6rem] py-4">
        <div className="flex items-center gap-[1.2rem]">
          <div className="grid h-[4.2rem] w-[4.2rem] place-items-center rounded-xl border border-[#1a2642] bg-[#111a30] text-[#b8c3dc]">
            <Svg d={CAP} className="h-10 w-10" />
          </div>
          <div>
            <h1 className={`${DISPLAY} text-[2.5rem] font-bold uppercase leading-[1.05] tracking-[.01em]`}>Sam&apos;s Driving School LLC</h1>
            <p className="mt-1 text-[1.15rem] text-[#93a0bd]">Smart Lobby TV • Live Dispatch Terminal</p>
          </div>
        </div>

        <div className={`${DISPLAY} inline-flex items-center gap-3 rounded-full border-2 border-[#22ff88] bg-[#22ff88]/10 px-6 py-[.6rem] text-[1.5rem] font-bold uppercase tracking-[.02em] text-[#22ff88] shadow-[0_0_1.4rem_rgba(34,255,136,.35),inset_0_0_.9rem_rgba(34,255,136,.15)]`}>
          <span className="lb-blink h-[.8rem] w-[.8rem] rounded-full bg-[#22ff88] shadow-[0_0_.8rem_#22ff88]" />
          Live Lobby Board
        </div>

        <div className="flex items-center justify-end gap-[1.6rem]">
          <div className="text-right">
            <div className={`${DISPLAY} text-[3.4rem] font-bold leading-none tabular-nums text-[#ffd60a] [text-shadow:0_0_1.2rem_rgba(255,214,10,.45)]`} suppressHydrationWarning>
              {now ? now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '--:--:-- --'}
            </div>
            <div className="mt-[.35rem] text-[1.15rem] text-[#93a0bd]" suppressHydrationWarning>
              {now?.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
            </div>
          </div>
          <div className="flex gap-3">
            <button className={`${tool} ${soundOn ? '' : 'opacity-45'}`} onClick={() => setSoundOn((s) => !s)} aria-label="Toggle sound"><Svg d={SOUND} className="h-7 w-7" /></button>
            <button className={tool} onClick={chime} aria-label="Test chime"><Svg d={BELL} className="h-7 w-7" /></button>
            <button className={tool} onClick={toggleFullscreen} aria-label="Fullscreen"><Svg d={FULL} className="h-7 w-7" /></button>
          </div>
        </div>
      </header>

      {/* Cards */}
      <main className={`grid min-h-0 flex-1 grid-cols-3 items-stretch gap-[2.2rem] px-[.2rem] py-[.4rem] transition-opacity duration-[450ms] ${fading ? 'opacity-0' : 'opacity-100'}`} aria-live="polite">
        {visible.map((l) => <LessonCard key={l.id} lesson={l} />)}
      </main>

      {/* Pager dots */}
      <div className="flex min-h-[.8rem] justify-center gap-3">
        {pages > 1 && Array.from({ length: pages }, (_, i) => (
          <i key={i} className={`h-[.8rem] w-[.8rem] rounded-full transition ${i === safePage ? 'bg-[#22ff88] shadow-[0_0_.7rem_#22ff88]' : 'bg-[#26314f]'}`} />
        ))}
      </div>

      {/* Announcement ticker */}
      {announcement && (
        <footer className="-mx-[2.2rem] flex h-[3.6rem] items-center overflow-hidden border-t border-[#1a2642] bg-[#070d1b]">
          <span className={`${DISPLAY} mx-[1.2rem] ml-[1.4rem] flex-none rounded-full bg-[#22ff88] px-[1.1rem] py-[.3rem] text-[1.15rem] font-bold uppercase tracking-[.03em] text-[#032414]`}>Announcement</span>
          <div className="flex-1 overflow-hidden whitespace-nowrap">
            <span className="lb-marquee text-[1.3rem] text-[#dfe6f6]">{announcement} &nbsp;•&nbsp; {announcement}</span>
          </div>
        </footer>
      )}
    </div>
  );
}
