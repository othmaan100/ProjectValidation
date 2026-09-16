<?php
/**
 * Question bank for the System Evaluation module.
 * Source: FEDERAL UNIVERSITY DUTSE, NIGERIA - System Evaluation Survey Questionnaires
 * (Student / Supervisor / Coordinator), verbatim.
 *
 * Section types:
 *   'choice' - single-choice radio question (Section A usage-profile items)
 *   'likert' - 1-5 Strongly Disagree..Strongly Agree radio group
 *   'text'   - required open-ended textarea
 */

function evaluation_questionnaire_definitions() {
    return [
        'student' => [
            'version' => 'student_v1',
            'title'   => 'System Evaluation Survey Questionnaire',
            'target'  => 'Student',
            'sections' => [
                [
                    'key' => 'A', 'title' => 'Section A: Experience and Use', 'type' => 'choice',
                    'questions' => [
                        ['no' => 'A1', 'text' => 'How frequently have you used the system?', 'options' => ['Once', '2–3 times', '4–6 times', 'More than 6 times']],
                        ['no' => 'A2', 'text' => 'Which system function have you used most?', 'options' => ['Topic submission', 'Proposal/status tracking', 'Messaging', 'Progress monitoring', 'Other']],
                        ['no' => 'A3', 'text' => 'Before using this system, how did you normally follow up on project topic matters?', 'options' => ['Physical office visits', 'Email/messaging', 'Department notice/records', 'Other']],
                    ],
                ],
                [
                    'key' => 'B', 'title' => 'Section B: System Quality and Ease of Use', 'type' => 'likert',
                    'questions' => [
                        ['no' => '1', 'text' => 'The student dashboard is easy to understand and navigate.'],
                        ['no' => '2', 'text' => 'Topic submission is straightforward and easy to complete.'],
                        ['no' => '3', 'text' => 'The system clearly indicates the information required before submission.'],
                        ['no' => '4', 'text' => 'The system responds within an acceptable time.'],
                        ['no' => '5', 'text' => 'The system is available when I need to use it.'],
                        ['no' => '6', 'text' => 'The system works well on the device/browser I normally use.'],
                        ['no' => '7', 'text' => 'The system provides clear messages, alerts, and notifications.'],
                        ['no' => '8', 'text' => 'I can easily find my proposals and related project information.'],
                    ],
                ],
                [
                    'key' => 'C', 'title' => 'Section C: Topic Validation and Transparency', 'type' => 'likert',
                    'questions' => [
                        ['no' => '9', 'text' => 'I can see the current status of my submitted proposal.'],
                        ['no' => '10', 'text' => 'The validation status (e.g., Pending, Validating, Under Review, Approved, Rejected) is clear.'],
                        ['no' => '11', 'text' => 'Validation results are communicated to me in a timely manner.'],
                        ['no' => '12', 'text' => 'The system makes the topic validation process more transparent than the previous/manual process.'],
                        ['no' => '13', 'text' => 'When a proposal is rejected or requires revision, the feedback is useful.'],
                        ['no' => '14', 'text' => 'The system helps reduce the possibility of proposing a duplicate topic.'],
                        ['no' => '15', 'text' => "The system's validation process gives me confidence that my topic has been properly reviewed."],
                    ],
                ],
                [
                    'key' => 'D', 'title' => 'Section D: Communication and Project Monitoring', 'type' => 'likert',
                    'questions' => [
                        ['no' => '16', 'text' => 'The messaging/communication feature makes it easier to communicate with my supervisor or coordinator.'],
                        ['no' => '17', 'text' => 'I receive useful notifications about important project activities.'],
                        ['no' => '18', 'text' => 'The system helps me keep track of project progress and milestones.'],
                        ['no' => '19', 'text' => 'The system reduces the need for repeated physical visits or informal follow-ups.'],
                        ['no' => '20', 'text' => 'Project information and decisions are easier to trace through the system.'],
                    ],
                ],
                [
                    'key' => 'E', 'title' => 'Section E: Information Quality, Satisfaction and Benefits', 'type' => 'likert',
                    'questions' => [
                        ['no' => '21', 'text' => 'Information displayed about my project is accurate.'],
                        ['no' => '22', 'text' => 'Project information is sufficiently complete for my needs.'],
                        ['no' => '23', 'text' => 'The system protects my project information appropriately.'],
                        ['no' => '24', 'text' => 'The system has saved me time in managing my project topic.'],
                        ['no' => '25', 'text' => 'The system has improved my overall project-management experience.'],
                        ['no' => '26', 'text' => 'Overall, I am satisfied with the system.'],
                        ['no' => '27', 'text' => 'I would recommend continued use of the system for student project management.'],
                    ],
                ],
                [
                    'key' => 'F', 'title' => 'Section F: Open-Ended Feedback', 'type' => 'text',
                    'questions' => [
                        ['no' => 'F1', 'text' => 'What is the most useful feature of the system for you?'],
                        ['no' => 'F2', 'text' => 'What problems, errors, or difficulties have you experienced while using the system?'],
                        ['no' => 'F3', 'text' => 'What feature or improvement would you most like to see added?'],
                        ['no' => 'F4', 'text' => 'Please provide any other comment or recommendation.'],
                    ],
                ],
            ],
        ],

        'supervisor' => [
            'version' => 'supervisor_v1',
            'title'   => 'System Evaluation Survey Questionnaire',
            'target'  => 'Supervisor',
            'sections' => [
                [
                    'key' => 'A', 'title' => 'Section A: Usage Profile', 'type' => 'choice',
                    'questions' => [
                        ['no' => 'A1', 'text' => 'How many students have you supervised through the system?', 'options' => ['1–5', '6–10', '11–20', 'More than 20']],
                        ['no' => 'A2', 'text' => 'Which supervisor function do you use most?', 'options' => ['Assigned students', 'Progress assessment', 'Defence grading', 'Messaging', 'Viewing project/topic details']],
                        ['no' => 'A3', 'text' => 'How often do you access the supervisor dashboard?', 'options' => ['Daily', 'Several times a week', 'Weekly', 'Occasionally']],
                    ],
                ],
                [
                    'key' => 'B', 'title' => 'Section B: Supervisor Dashboard and System Quality', 'type' => 'likert',
                    'questions' => [
                        ['no' => '1', 'text' => 'The supervisor dashboard is easy to navigate.'],
                        ['no' => '2', 'text' => 'Assigned students and their project details are presented clearly.'],
                        ['no' => '3', 'text' => 'I can quickly locate information about a particular student/project.'],
                        ['no' => '4', 'text' => 'Assessment forms are clear and easy to complete.'],
                        ['no' => '5', 'text' => 'The system responds within an acceptable time.'],
                        ['no' => '6', 'text' => 'The system is reliable during normal use.'],
                        ['no' => '7', 'text' => 'Notifications and alerts are clear and timely.'],
                        ['no' => '8', 'text' => 'The system provides adequate security and access control for supervisor activities.'],
                    ],
                ],
                [
                    'key' => 'C', 'title' => 'Section C: Supervision, Assessment and Communication', 'type' => 'likert',
                    'questions' => [
                        ['no' => '9', 'text' => 'The system makes it easier to monitor students assigned to me.'],
                        ['no' => '10', 'text' => 'The system supports systematic tracking of student progress.'],
                        ['no' => '11', 'text' => 'The assessment process is more organized than the previous/manual process.'],
                        ['no' => '12', 'text' => 'I can provide feedback to students conveniently through the system.'],
                        ['no' => '13', 'text' => 'Communication with students is more traceable through the platform.'],
                        ['no' => '14', 'text' => 'Communication with project coordinators is improved through the system.'],
                        ['no' => '15', 'text' => 'The system helps me meet project assessment/supervision responsibilities on time.'],
                        ['no' => '16', 'text' => 'Defence scheduling/grading functions, where used, are easy to operate.'],
                    ],
                ],
                [
                    'key' => 'D', 'title' => 'Section D: Information Quality and Transparency', 'type' => 'likert',
                    'questions' => [
                        ['no' => '17', 'text' => 'Student/project information available to me is accurate.'],
                        ['no' => '18', 'text' => 'The information provided is sufficiently complete for supervision decisions.'],
                        ['no' => '19', 'text' => 'Project status information is current and useful.'],
                        ['no' => '20', 'text' => 'The system provides adequate visibility into the project lifecycle.'],
                        ['no' => '21', 'text' => 'Records of assessments and feedback are easy to retrieve when needed.'],
                    ],
                ],
                [
                    'key' => 'E', 'title' => 'Section E: Satisfaction, Efficiency and Net Benefits', 'type' => 'likert',
                    'questions' => [
                        ['no' => '22', 'text' => 'The system reduces time spent on routine project administration.'],
                        ['no' => '23', 'text' => 'The system reduces reliance on scattered emails, messages, and physical records.'],
                        ['no' => '24', 'text' => 'The system improves accountability in project supervision.'],
                        ['no' => '25', 'text' => 'The system improves transparency of project-related activities.'],
                        ['no' => '26', 'text' => 'The system has improved my overall effectiveness as a supervisor.'],
                        ['no' => '27', 'text' => 'Overall, I am satisfied with the system.'],
                        ['no' => '28', 'text' => 'I would recommend continued use of the system.'],
                    ],
                ],
                [
                    'key' => 'F', 'title' => 'Section F: Open-Ended Feedback', 'type' => 'text',
                    'questions' => [
                        ['no' => 'F1', 'text' => 'Which system feature has provided the greatest benefit to your supervision work?'],
                        ['no' => 'F2', 'text' => 'What difficulties, errors, or limitations have you encountered?'],
                        ['no' => 'F3', 'text' => 'What changes would make the system more useful to supervisors?'],
                        ['no' => 'F4', 'text' => 'What additional reports, assessments, notifications, or tools should be included?'],
                        ['no' => 'F5', 'text' => 'Any other comments or recommendations?'],
                    ],
                ],
            ],
        ],

        'coordinator' => [
            'version' => 'coordinator_v1',
            'title'   => 'System Evaluation Survey Questionnaire',
            'target'  => 'Departmental / Faculty Project Coordinator',
            'sections' => [
                [
                    'key' => 'A', 'title' => 'Section A: Coordinator Profile', 'type' => 'choice',
                    'questions' => [
                        ['no' => 'A1', 'text' => 'Your coordination role:', 'options' => ['Department Project Coordinator (DPC)', 'Faculty Project Coordinator (FPC)', 'Both DPC and FPC']],
                        ['no' => 'A2', 'text' => 'Which function do you use most?', 'options' => ['Topic validation', 'Supervisor assignment', 'Historical records', 'Reports/analytics', 'User/workflow management']],
                        ['no' => 'A3', 'text' => 'How frequently do you use the system?', 'options' => ['Daily', 'Several times a week', 'Weekly', 'Occasionally']],
                    ],
                ],
                [
                    'key' => 'B', 'title' => 'Section B: Coordinator Interface and System Quality', 'type' => 'likert',
                    'questions' => [
                        ['no' => '1', 'text' => 'The coordinator dashboard is well organized and easy to navigate.'],
                        ['no' => '2', 'text' => 'Pending project activities are easy to identify.'],
                        ['no' => '3', 'text' => 'The system provides an acceptable response time during coordination tasks.'],
                        ['no' => '4', 'text' => 'The system is reliable for routine project administration.'],
                        ['no' => '5', 'text' => 'Role-based access provides appropriate access to departmental/faculty information.'],
                        ['no' => '6', 'text' => 'Search, filtering, and record retrieval functions are effective.'],
                        ['no' => '7', 'text' => 'System notifications and workflow alerts are clear and timely.'],
                        ['no' => '8', 'text' => 'The system provides adequate auditability/accountability of user actions.'],
                    ],
                ],
                [
                    'key' => 'C', 'title' => 'Section C: Topic Validation and Hybrid AI Validation', 'type' => 'likert',
                    'questions' => [
                        ['no' => '9', 'text' => 'The topic validation workflow is clear and easy to follow.'],
                        ['no' => '10', 'text' => 'Local similarity results are useful for identifying possible duplicate topics.'],
                        ['no' => '11', 'text' => 'Similarity percentages/results are presented clearly enough to support decisions.'],
                        ['no' => '12', 'text' => 'The AI-generated assessment provides useful additional insight into topic soundness, originality, scope, and clarity.'],
                        ['no' => '13', 'text' => 'The combined local-similarity and AI approach improves the validation process.'],
                        ['no' => '14', 'text' => 'The validation report provides sufficient information for approve/reject/request-revision decisions.'],
                        ['no' => '15', 'text' => 'The system reduces the time required to validate project topics.'],
                        ['no' => '16', 'text' => 'The system reduces the risk of topic duplication compared with the previous/manual process.'],
                    ],
                ],
                [
                    'key' => 'D', 'title' => 'Section D: Workflow, Supervision Assignment and Communication', 'type' => 'likert',
                    'questions' => [
                        ['no' => '17', 'text' => 'Approval, rejection, and request-for-revision actions are easy to perform.'],
                        ['no' => '18', 'text' => 'Feedback/comments to students can be provided effectively.'],
                        ['no' => '19', 'text' => 'Supervisor assignment is straightforward.'],
                        ['no' => '20', 'text' => 'Supervisor workload information is useful when making assignments.'],
                        ['no' => '21', 'text' => 'The system improves communication between coordinators, students, and supervisors.'],
                        ['no' => '22', 'text' => 'The workflow provides adequate transparency from submission through approval and supervision.'],
                        ['no' => '23', 'text' => 'The system reduces administrative delays and repeated manual follow-ups.'],
                    ],
                ],
                [
                    'key' => 'E', 'title' => 'Section E: Records, Reporting and Information Quality', 'type' => 'likert',
                    'questions' => [
                        ['no' => '24', 'text' => 'Historical project records are easy to upload/manage.'],
                        ['no' => '25', 'text' => 'Historical project records are sufficiently searchable and useful for validation.'],
                        ['no' => '26', 'text' => 'The system improves institutional memory of previous projects.'],
                        ['no' => '27', 'text' => 'Project records are accurate and sufficiently complete.'],
                        ['no' => '28', 'text' => 'Reports provide useful information for departmental/faculty planning.'],
                        ['no' => '29', 'text' => 'Exported/printed reports are suitable for administrative and accreditation purposes.'],
                        ['no' => '30', 'text' => 'The system makes it easier to retrieve project records when needed.'],
                        ['no' => '31', 'text' => 'The system improves accountability and documentation of project decisions.'],
                    ],
                ],
                [
                    'key' => 'F', 'title' => 'Section F: Service Quality, Satisfaction and Institutional Benefits', 'type' => 'likert',
                    'questions' => [
                        ['no' => '32', 'text' => 'Training/support provided for the system is adequate.'],
                        ['no' => '33', 'text' => 'Help or assistance is available when technical/user problems occur.'],
                        ['no' => '34', 'text' => 'The system has reduced administrative workload.'],
                        ['no' => '35', 'text' => 'The system has improved coordination efficiency.'],
                        ['no' => '36', 'text' => 'The system has improved transparency of project management.'],
                        ['no' => '37', 'text' => 'The system supports better academic integrity through topic-level validation.'],
                        ['no' => '38', 'text' => 'The system is suitable for continued institutional use.'],
                        ['no' => '39', 'text' => 'Overall, I am satisfied with the system.'],
                    ],
                ],
                [
                    'key' => 'G', 'title' => 'Section G: Open-Ended Feedback', 'type' => 'text',
                    'questions' => [
                        ['no' => 'G1', 'text' => "What is the most valuable feature of the system from a coordinator's perspective?"],
                        ['no' => 'G2', 'text' => 'What weaknesses, errors, workflow bottlenecks, or technical problems have you observed?'],
                        ['no' => 'G3', 'text' => 'How can the hybrid validation engine or validation reports be improved?'],
                        ['no' => 'G4', 'text' => 'What additional administrative, reporting, analytics, or workflow features are needed?'],
                        ['no' => 'G5', 'text' => 'What changes would improve adoption and user satisfaction across departments/faculties?'],
                        ['no' => 'G6', 'text' => 'Any other comments or recommendations?'],
                    ],
                ],
            ],
        ],
    ];
}

/**
 * Maps a system login role (users.role) to the questionnaire key it must complete.
 * HOD and Coordinators (DPC/FPC) share the same "coordinator" instrument.
 */
function evaluation_role_key($system_role) {
    switch ($system_role) {
        case 'stu': return 'student';
        case 'sup': return 'supervisor';
        case 'dpc':
        case 'fpc':
        case 'hod': return 'coordinator';
        default: return null;
    }
}
