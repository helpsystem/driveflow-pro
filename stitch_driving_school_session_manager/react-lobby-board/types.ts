export type LessonStatus =
  | 'active'     // green  – on the road
  | 'ready'      // green  – ready to start class
  | 'waiting'    // yellow – awaiting class start
  | 'upcoming'   // yellow – next in line
  | 'cancelled'  // red    – cancelled
  | 'completed'; // blue   – finished

export interface Lesson {
  id: string;
  student: string;
  instructor: string;
  lesson: string;
  plate: string;
  /** Fuel level in percent (0–100). Omit if unknown and the gauge is hidden. */
  fuel?: number;
  start: string;
  end: string;
  status: LessonStatus;
}
