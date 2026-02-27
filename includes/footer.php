<?php
// includes/footer.php
?>
        </div> <!-- Fecha container-fluid do main -->
    </main> <!-- Fecha main -->

    <!-- Footer -->
    <footer class="bg-light py-3 mt-auto">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 text-center text-muted">
                    <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos os direitos reservados.</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>

<!-- Modal Informativo sobre PDI - VERSÃO MELHORADA -->
<div class="modal fade" id="modalPdiInfo" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
                <h5 class="modal-title">
                    <i class="bi bi-diagram-3"></i> Guia Completo do PDI
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Introdução -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i>
                    <strong>PDI (Plano de Desenvolvimento Individual)</strong> é uma ferramenta estratégica que mapeia o crescimento profissional do colaborador, definindo onde ele está, onde quer chegar e como vai chegar lá.
                </div>

                <!-- Cards de Resumo -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            O que é?</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">Plano de Desenvolvimento</div>
                                        <small>Roteiro personalizado de crescimento</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-diagram-3 fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Para quem?</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">Todos os Colaboradores</div>
                                        <small>Da base à liderança</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Benefícios</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">Crescimento + Retenção</div>
                                        <small>Engajamento e carreira</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-graph-up-arrow fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metas SMART -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-bullseye"></i> Metas SMART</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <div class="border p-3 rounded bg-primary text-white">
                                    <h4>S</h4>
                                    <small>Específica</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="border p-3 rounded bg-success text-white">
                                    <h4>M</h4>
                                    <small>Mensurável</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="border p-3 rounded bg-warning">
                                    <h4>A</h4>
                                    <small>Alcançável</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="border p-3 rounded bg-info text-white">
                                    <h4>R</h4>
                                    <small>Relevante</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="border p-3 rounded bg-danger text-white">
                                    <h4>T</h4>
                                    <small>Temporal</small>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Letra</th>
                                        <th>Significado</th>
                                        <th>Exemplo RUIM</th>
                                        <th>Exemplo BOM</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-primary">S</span></td>
                                        <td><strong>Específica</strong> - Clara e detalhada</td>
                                        <td>"Melhorar vendas"</td>
                                        <td>"Aumentar vendas do produto X em 15%"</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-success">M</span></td>
                                        <td><strong>Mensurável</strong> - Dá para medir</td>
                                        <td>"Atender melhor"</td>
                                        <td>"Atingir 90% de satisfação nas pesquisas"</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-warning">A</span></td>
                                        <td><strong>Alcançável</strong> - Realista</td>
                                        <td>"Virar milionário em 1 mês"</td>
                                        <td>"Aumentar salário em 20% em 1 ano"</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-info">R</span></td>
                                        <td><strong>Relevante</strong> - Alinhado com objetivos</td>
                                        <td>"Aprender violão" (se não for relevante)</td>
                                        <td>"Fazer curso de liderança" (para gestores)</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-danger">T</span></td>
                                        <td><strong>Temporal</strong> - Tem prazo</td>
                                        <td>"Um dia"</td>
                                        <td>"Até 30/06/2024"</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modelo 70-20-10 -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Modelo 70-20-10</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-primary text-white text-center">
                                    <div class="card-body">
                                        <h1 class="display-4">70%</h1>
                                        <h5>Experiência Prática</h5>
                                        <p class="small">Projetos desafiadores, job rotation, novas responsabilidades</p>
                                        <i class="bi bi-tools"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white text-center">
                                    <div class="card-body">
                                        <h1 class="display-4">20%</h1>
                                        <h5>Relacionamento</h5>
                                        <p class="small">Mentoria, coaching, feedback, networking</p>
                                        <i class="bi bi-people"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-center">
                                    <div class="card-body">
                                        <h1 class="display-4">10%</h1>
                                        <h5>Educação Formal</h5>
                                        <p class="small">Cursos, treinamentos, workshops, leituras</p>
                                        <i class="bi bi-book"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ciclo do PDI -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> Ciclo do PDI</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col">
                                <div class="border p-3 rounded bg-light">
                                    <span class="badge bg-primary rounded-circle p-3 mb-2">1</span>
                                    <h6>Diagnóstico</h6>
                                    <small>Baseado nas avaliações</small>
                                </div>
                                <i class="bi bi-arrow-right d-none d-md-block"></i>
                            </div>
                            <div class="col">
                                <div class="border p-3 rounded bg-light">
                                    <span class="badge bg-success rounded-circle p-3 mb-2">2</span>
                                    <h6>Planejamento</h6>
                                    <small>Metas SMART</small>
                                </div>
                                <i class="bi bi-arrow-right d-none d-md-block"></i>
                            </div>
                            <div class="col">
                                <div class="border p-3 rounded bg-light">
                                    <span class="badge bg-warning rounded-circle p-3 mb-2">3</span>
                                    <h6>Execução</h6>
                                    <small>Ações 70-20-10</small>
                                </div>
                                <i class="bi bi-arrow-right d-none d-md-block"></i>
                            </div>
                            <div class="col">
                                <div class="border p-3 rounded bg-light">
                                    <span class="badge bg-info rounded-circle p-3 mb-2">4</span>
                                    <h6>Acompanhamento</h6>
                                    <small>Check-ins regulares</small>
                                </div>
                                <i class="bi bi-arrow-right d-none d-md-block"></i>
                            </div>
                            <div class="col">
                                <div class="border p-3 rounded bg-light">
                                    <span class="badge bg-danger rounded-circle p-3 mb-2">5</span>
                                    <h6>Revisão</h6>
                                    <small>Ajustes e conclusão</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dicas Rápidas -->
                <div class="alert alert-success">
                    <i class="bi bi-lightbulb"></i>
                    <strong>Dicas de Ouro:</strong>
                    <ul class="mb-0 mt-2">
                        <li>🎯 Metas muito fáceis = sem desafio / Metas impossíveis = frustração</li>
                        <li>📊 Acompanhe o progresso regularmente (mensal é ideal)</li>
                        <li>🤝 Envolva o gestor no processo - não faça sozinho</li>
                        <li>📝 Documente cada conquista - servirá para promoções</li>
                        <li>🔄 Revise e ajuste as metas sempre que necessário</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?php echo SITE_URL; ?>/modules/pdi/criar.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Criar meu PDI
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}
.border-left-primary {
    border-left: 4px solid #4e73df;
}
.border-left-success {
    border-left: 4px solid #1cc88a;
}
.border-left-info {
    border-left: 4px solid #36b9cc;
}
</style>



</body>
</html>
