import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import {
    Search,
    Plus,
    Mail,
    Phone,
    Loader2,
    ExternalLink,
} from 'lucide-react';
import { useAuth } from '../hooks/useAuth';

interface Client {
    client_id: string;
    client_code: string;
    name: string;
    email: string;
    phone: string;
    address: string;
    status: string;
    project_id?: string;
    project_name?: string;
}

export const Clients: React.FC = () => {
    const { user } = useAuth();
    const navigate = useNavigate();
    const [clients, setClients] = useState<Client[]>([]);
    const [loading, setLoading] = useState(true);
    const [showAddModal, setShowAddModal] = useState(false);
    const [showProjectModal, setShowProjectModal] = useState(false);
    const [selectedClient, setSelectedClient] = useState<Client | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [team, setTeam] = useState<any[]>([]);

    const [clientForm, setClientForm] = useState({
        name: '',
        email: '',
        phone: '',
        address: ''
    });

    const [projectForm, setProjectForm] = useState({
        project_name: '',
        project_head_id: '',
        site_engineer_id: '',
        start_date: new Date().toISOString().split('T')[0],
        expected_end_date: new Date(new Date().setMonth(new Date().getMonth() + 3)).toISOString().split('T')[0]
    });

    useEffect(() => {
        fetchClients();
        fetchTeam();
    }, []);

    const fetchClients = async () => {
        try {
            const response = await axios.get('http://localhost/ARK/api/clients');
            if (response.data.success) {
                setClients(response.data.data);
            }
        } catch (err) {
            console.error('Failed to fetch clients');
        } finally {
            setLoading(false);
        }
    };

    const fetchTeam = async () => {
        try {
            const response = await axios.get('http://localhost/ARK/api/users');
            if (response.data.success) {
                setTeam(response.data.data);
            }
        } catch (err) {
            console.error('Failed to fetch team');
        }
    };

    const handleAddClient = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            const response = await axios.post('http://localhost/ARK/api/clients', {
                ...clientForm,
                created_by: user?.user_id
            });
            if (response.data.success) {
                setShowAddModal(false);
                setClientForm({ name: '', email: '', phone: '', address: '' });
                fetchClients();
            }
        } catch (err) {
            alert('Failed to add client');
        } finally {
            setSubmitting(false);
        }
    };

    const handleLaunchClick = (client: Client) => {
        setSelectedClient(client);
        setProjectForm({
            ...projectForm,
            project_name: client.name + ' Project'
        });
        setShowProjectModal(true);
    };

    const handleProjectLaunch = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            const response = await axios.post('http://localhost/ARK/api/projects', {
                ...projectForm,
                client_id: selectedClient?.client_id,
                total_budget: 0, // Set by accountant later
                expected_end_date: projectForm.expected_end_date,
                created_by: user?.user_id
            });
            if (response.data.success) {
                setShowProjectModal(false);
                navigate(`/projects/${response.data.project_id}`);
            }
        } catch (err) {
            alert('Failed to launch project');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="clients-page">
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                <div>
                    <h2 style={{ fontSize: '1.8rem', fontWeight: 700, color: '#253b50' }}>Registry</h2>
                    <p style={{ color: '#6b7280' }}>All registered clients and their project status</p>
                </div>
                <button
                    onClick={() => setShowAddModal(true)}
                    className="btn-primary"
                    style={{ display: 'flex', alignItems: 'center', gap: '8px' }}
                >
                    <Plus size={18} />
                    Add Registry
                </button>
            </div>

            <div className="premium-card">
                <div style={{ display: 'flex', gap: '16px', marginBottom: '24px' }}>
                    <div style={{ position: 'relative', flex: 1 }}>
                        <Search size={18} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#9ca3af' }} />
                        <input
                            type="text"
                            placeholder="Search by client name, code or phone..."
                            style={{ width: '100%', padding: '12px 12px 12px 40px', borderRadius: '8px', border: '1px solid #e5e7eb', outline: 'none' }}
                        />
                    </div>
                </div>

                <div style={{ overflowX: 'auto' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                        <thead>
                            <tr style={{ borderBottom: '1px solid #e5e7eb', textAlign: 'left' }}>
                                <th style={{ padding: '16px', color: '#6b7280', fontWeight: 500 }}>Code</th>
                                <th style={{ padding: '16px', color: '#6b7280', fontWeight: 500 }}>Name</th>
                                <th style={{ padding: '16px', color: '#6b7280', fontWeight: 500 }}>Contact</th>
                                <th style={{ padding: '16px', color: '#6b7280', fontWeight: 500 }}>Assign Team</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <tr><td colSpan={4} style={{ textAlign: 'center', padding: '40px' }}><Loader2 className="animate-spin" style={{ margin: '0 auto' }} /></td></tr>
                            ) : clients.length === 0 ? (
                                <tr><td colSpan={4} style={{ textAlign: 'center', padding: '40px', color: '#6b7280' }}>No entries found. Click "Add Registry" to create one.</td></tr>
                            ) : clients.map((client) => (
                                <tr key={client.client_id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                                    <td style={{ padding: '16px' }}>
                                        <span style={{ fontWeight: 700, color: '#253b50' }}>{client.client_code}</span>
                                    </td>
                                    <td style={{ padding: '16px' }}>
                                        <div style={{ fontWeight: 600 }}>{client.name}</div>
                                        <div style={{ fontSize: '0.8rem', color: '#6b7280' }}>{client.address}</div>
                                    </td>
                                    <td style={{ padding: '16px' }}>
                                        <div style={{ display: 'flex', flexDirection: 'column', fontSize: '0.85rem' }}>
                                            <span style={{ display: 'flex', alignItems: 'center', gap: '6px' }}><Mail size={12} /> {client.email}</span>
                                            <span style={{ display: 'flex', alignItems: 'center', gap: '6px' }}><Phone size={12} /> {client.phone}</span>
                                        </div>
                                    </td>
                                    <td style={{ padding: '16px' }}>
                                        {(user?.role === 'principal_architect' || user?.role === 'admin') && (
                                            client.project_id ? (
                                                <button
                                                    onClick={() => navigate(`/projects/${client.project_id}`)}
                                                    style={{ fontSize: '0.75rem', padding: '6px 12px', display: 'flex', alignItems: 'center', gap: '6px', backgroundColor: '#f3f4f6', color: '#374151', border: '1px solid #d1d5db', borderRadius: '6px', cursor: 'pointer', fontWeight: 600 }}
                                                >
                                                    <ExternalLink size={14} /> Manage Project
                                                </button>
                                            ) : (
                                                <button
                                                    onClick={() => handleLaunchClick(client)}
                                                    className="btn-primary"
                                                    style={{ fontSize: '0.75rem', padding: '6px 12px', display: 'flex', alignItems: 'center', gap: '6px' }}
                                                >
                                                    <ExternalLink size={14} /> Assign & Launch
                                                </button>
                                            )
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Add Client Modal */}
            {showAddModal && (
                <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000 }}>
                    <div className="premium-card" style={{ width: '100%', maxWidth: '500px' }}>
                        <h3 style={{ marginBottom: '24px', fontSize: '1.4rem' }}>Manual Registry Entry</h3>
                        <form onSubmit={handleAddClient}>
                            <div style={{ marginBottom: '16px' }}>
                                <label style={{ display: 'block', marginBottom: '6px', fontSize: '0.9rem', fontWeight: 500 }}>Full Name</label>
                                <input type="text" required value={clientForm.name} onChange={(e) => setClientForm({ ...clientForm, name: e.target.value })} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #d1d5db' }} />
                            </div>
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '16px' }}>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '6px', fontSize: '0.9rem', fontWeight: 500 }}>Phone Number</label>
                                    <input type="text" required value={clientForm.phone} onChange={(e) => setClientForm({ ...clientForm, phone: e.target.value })} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #d1d5db' }} />
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '6px', fontSize: '0.9rem', fontWeight: 500 }}>Email Address</label>
                                    <input type="email" value={clientForm.email} onChange={(e) => setClientForm({ ...clientForm, email: e.target.value })} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #d1d5db' }} />
                                </div>
                            </div>
                            <div style={{ marginBottom: '24px' }}>
                                <label style={{ display: 'block', marginBottom: '6px', fontSize: '0.9rem', fontWeight: 500 }}>Client Address</label>
                                <textarea rows={2} required value={clientForm.address} onChange={(e) => setClientForm({ ...clientForm, address: e.target.value })} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #d1d5db' }} />
                            </div>
                            <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end' }}>
                                <button type="button" onClick={() => setShowAddModal(false)} style={{ padding: '10px 20px', borderRadius: '6px', border: '1px solid #d1d5db', backgroundColor: 'white' }}>Cancel</button>
                                <button type="submit" disabled={submitting} className="btn-primary">
                                    {submitting ? 'Registering...' : 'Register Registry'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Launch Project Modal */}
            {showProjectModal && (
                <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000 }}>
                    <div className="premium-card" style={{ width: '100%', maxWidth: '650px' }}>
                        <h3 style={{ marginBottom: '24px', fontSize: '1.4rem' }}>Assign & Launch: {selectedClient?.name}</h3>
                        <p style={{ marginBottom: '20px', color: '#6b7280', fontSize: '0.9rem' }}>Select the project execution team. (Financial details to be entered by Accountant)</p>
                        <form onSubmit={handleProjectLaunch}>
                            <div style={{ marginBottom: '16px' }}>
                                <label style={{ display: 'block', marginBottom: '6px', fontSize: '0.9rem', fontWeight: 500 }}>Project Name</label>
                                <input type="text" required value={projectForm.project_name} onChange={(e) => setProjectForm({ ...projectForm, project_name: e.target.value })} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #d1d5db' }} />
                            </div>
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '16px' }}>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '6px', fontSize: '0.9rem', fontWeight: 500 }}>Project Head</label>
                                    <select required value={projectForm.project_head_id} onChange={(e) => setProjectForm({ ...projectForm, project_head_id: e.target.value })} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #d1d5db' }}>
                                        <option value="">Select Head</option>
                                        {team.filter(t => ['principal_architect', 'project_head', 'admin'].includes(t.role)).map(t => (
                                            <option key={t.user_id} value={t.user_id}>{t.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '6px', fontSize: '0.9rem', fontWeight: 500 }}>Site Engineer</label>
                                    <select required value={projectForm.site_engineer_id} onChange={(e) => setProjectForm({ ...projectForm, site_engineer_id: e.target.value })} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #d1d5db' }}>
                                        <option value="">Select Engineer</option>
                                        {team.filter(t => t.role === 'site_engineer').map(t => <option key={t.user_id} value={t.user_id}>{t.name}</option>)}
                                    </select>
                                </div>
                            </div>
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '24px' }}>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '6px', fontSize: '0.9rem', fontWeight: 500 }}>Start Date</label>
                                    <input type="date" required value={projectForm.start_date} onChange={(e) => setProjectForm({ ...projectForm, start_date: e.target.value })} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #d1d5db' }} />
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '6px', fontSize: '0.9rem', fontWeight: 500 }}>End Date</label>
                                    <input type="date" required value={projectForm.expected_end_date} onChange={(e) => setProjectForm({ ...projectForm, expected_end_date: e.target.value })} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #d1d5db' }} />
                                </div>
                            </div>
                            <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end' }}>
                                <button type="button" onClick={() => setShowProjectModal(false)} style={{ padding: '10px 20px', borderRadius: '6px', border: '1px solid #d1d5db', backgroundColor: 'white' }}>Cancel</button>
                                <button type="submit" disabled={submitting} className="btn-primary">
                                    {submitting ? 'Launching...' : 'Activate Project'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};
