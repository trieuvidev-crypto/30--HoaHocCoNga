'use strict';

/**
 * Canonical room names, matching 01_ARCHITECTURE_REPORT.md §6 Realtime
 * Architecture. Every place that needs a room name must use these
 * functions rather than constructing strings inline, so the naming
 * convention can never drift between files.
 */
module.exports = {
    userRoom: (userUuid) => `user:${userUuid}`,
    courseRoom: (courseUuid) => `course:${courseUuid}`,
    lessonRoom: (lessonUuid) => `lesson:${lessonUuid}`,
    quizRoom: (quizUuid) => `quiz:${quizUuid}`,
    liveClassRoom: (liveClassUuid) => `live:${liveClassUuid}`,
    teacherRoom: (teacherUuid) => `teacher:${teacherUuid}`,
    ADMIN: 'admin',
    FORUM: 'forum',
    GLOBAL: 'global',
};
