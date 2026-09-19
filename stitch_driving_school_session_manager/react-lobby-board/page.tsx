import { Roboto, Roboto_Condensed } from 'next/font/google';
import './lobby.css';
import LobbyBoard from '../../components/lobby/LobbyBoard';
import { DEMO_LESSONS } from '../../components/lobby/demo-data';

const display = Roboto_Condensed({ subsets: ['latin'], weight: ['500', '600', '700'], variable: '--font-display' });
const body = Roboto({ subsets: ['latin'], weight: ['400', '500', '700'], variable: '--font-body' });

export const metadata = { title: "Live Lobby Board — Sam's Driving School LLC" };

export default function LobbyPage() {
  return (
    <div className={`${display.variable} ${body.variable}`}>
      <LobbyBoard
        initialLessons={DEMO_LESSONS}
        endpoint="/api/lobby"   // remove this line to disable live polling
        announcement="Welcome to Sam's Driving School LLC • Please have your Maryland learner permit ready • Safe driving is respect for life • Maryland MVA COMAR 11.23 Compliant"
      />
    </div>
  );
}
