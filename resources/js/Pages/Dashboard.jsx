import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({ stats }) {
    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h1 className="text-3xl font-bold mb-6">Dashboard</h1>

                            {/* Stats Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div className="bg-blue-50 p-6 rounded-lg">
                                    <div className="text-sm font-medium text-blue-600">Total Students</div>
                                    <div className="mt-2 text-3xl font-bold text-blue-900">
                                        {stats?.total_students || 0}
                                    </div>
                                </div>

                                <div className="bg-green-50 p-6 rounded-lg">
                                    <div className="text-sm font-medium text-green-600">Total Teachers</div>
                                    <div className="mt-2 text-3xl font-bold text-green-900">
                                        {stats?.total_teachers || 0}
                                    </div>
                                </div>

                                <div className="bg-purple-50 p-6 rounded-lg">
                                    <div className="text-sm font-medium text-purple-600">Total Subjects</div>
                                    <div className="mt-2 text-3xl font-bold text-purple-900">
                                        {stats?.total_subjects || 0}
                                    </div>
                                </div>

                                <div className="bg-yellow-50 p-6 rounded-lg">
                                    <div className="text-sm font-medium text-yellow-600">Total Revenue</div>
                                    <div className="mt-2 text-3xl font-bold text-yellow-900">
                                        ${stats?.total_revenue || 0}
                                    </div>
                                </div>
                            </div>

                            {/* Additional content will go here */}
                            <div className="mt-8">
                                <h2 className="text-xl font-semibold mb-4">Recent Activity</h2>
                                <p className="text-gray-600">Welcome to the School Registration System dashboard!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
