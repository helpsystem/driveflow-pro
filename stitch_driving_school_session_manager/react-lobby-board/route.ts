import { NextResponse } from 'next/server';
import { DEMO_LESSONS } from '../../../components/lobby/demo-data';

export const dynamic = 'force-dynamic';

// Replace with your database / dispatch system query.
export async function GET() {
  return NextResponse.json(DEMO_LESSONS);
}
