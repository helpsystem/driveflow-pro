import type { Lesson } from './types';

export const DEMO_LESSONS: Lesson[] = [
  { id: '1', student: 'Marcus Johnson', instructor: 'Mr. Anderson', lesson: 'Lesson 5 · Highway Merging',  plate: '8WD 4931', fuel: 62, start: '9:00 AM',  end: '11:00 AM', status: 'active' },
  { id: '2', student: 'Sarah Chen',     instructor: 'Ms. Rivera',   lesson: 'Lesson 5 · Highway Merging',  plate: '8WD 4931', fuel: 78, start: '10:30 AM', end: '12:30 PM', status: 'upcoming' },
  { id: '3', student: 'David Kim',      instructor: 'Mr. Thompson', lesson: 'Lesson 5 · Highway Merging',  plate: '8WD 4931', fuel: 34, start: '7:00 AM',  end: '9:00 AM',  status: 'completed' },
  { id: '4', student: 'Emily Carter',   instructor: 'Mr. Anderson', lesson: 'Lesson 3 · Parallel Parking', plate: '4KT 2208', fuel: 91, start: '11:00 AM', end: '1:00 PM',  status: 'ready' },
  { id: '5', student: 'Omar Hassan',    instructor: 'Ms. Rivera',   lesson: 'Lesson 2 · City Driving',     plate: '6PL 7715', fuel: 12, start: '1:00 PM',  end: '3:00 PM',  status: 'waiting' },
  { id: '6', student: 'Jessica Lee',    instructor: 'Mr. Thompson', lesson: 'Lesson 6 · Night Driving',    plate: '2MR 9046', fuel: 45, start: '3:30 PM',  end: '5:30 PM',  status: 'cancelled' },
];
